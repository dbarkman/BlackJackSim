<?php

$iterations = 10;

$playerBlackjack = 0;
$playerWins = 0;
$playerBusts = 0;
$dealerBlackjack = 0;
$dealerWins = 0;
$dealerBusts = 0;
$push = 0;
$surrender = 0;

for ($i = 0; $i < $iterations; $i++) {
    $bj = new BlackJack();
    $winner = $bj->winner;
    foreach($winner as $win) {
        switch($win) {
            case 0:
                $playerBlackjack++;
                break;
            case 1:
                $playerWins++;
                break;
            case 2:
                $playerBusts++;
                break;
            case 3:
                $dealerBlackjack++;
                break;
            case 4:
                $dealerWins++;
                break;
            case 5:
                $dealerBusts++;
                break;
            case 6:
                $push++;
                break;
            case 7:
                $surrender++;
                break;
        }
    }
}

echo "Results:" . PHP_EOL;
echo "Player Blackjacks: " . $playerBlackjack . " - " . ($playerBlackjack / $iterations * 100) . "%" . PHP_EOL;
echo "Player Wins: " . $playerWins . " - " . ($playerWins / $iterations * 100) . "%" . PHP_EOL;
echo "Player Busts: " . $playerBusts . " - " . ($playerBusts / $iterations * 100) . "%" . PHP_EOL;
echo "Dealer Blackjacks: " . $dealerBlackjack . " - " . ($dealerBlackjack / $iterations * 100) . "%" . PHP_EOL;
echo "Dealer Wins: " . $dealerWins . " - " . ($dealerWins / $iterations * 100) . "%" . PHP_EOL;
echo "Dealer Busts: " . $dealerBusts . " - " . ($dealerBusts / $iterations * 100) . "%" . PHP_EOL;
echo "Push: " . $push . " - " . ($push / $iterations * 100) . "%" . PHP_EOL;
echo "Surrender: " . $surrender . " - " . ($surrender / $iterations * 100) . "%" . PHP_EOL;
echo PHP_EOL;
echo "Player: " . ($playerBlackjack + $playerWins + $dealerBusts) . " - " . (($playerBlackjack + $playerWins + $dealerBusts) / $iterations * 100) . "%" . PHP_EOL;
echo "Dealer: " . ($dealerBlackjack + $dealerWins + $playerBusts) . " - " . (($dealerBlackjack + $dealerWins + $playerBusts) / $iterations * 100) . "%" . PHP_EOL;
echo "Tie: " . ($push + $surrender) . " - " . (($push + $surrender) / $iterations * 100) . "%" . PHP_EOL;
echo PHP_EOL;

class BlackJack
{
    private $strategyFile;
    private $decksUsed;
    private $suit;
    private $deck;
    private $shoe;
    private $playerCards;//needs to hold multiple for splits
    private $dealerCards;
    private $playerCard1;
    private $playerCard2;
    private $dealerCard1;
    private $dealerCard2;
    private $playerTotal;//needs to hold multiple for splits
    private $dealerTotal;
    private $playerBusts;//needs to hold multiple for splits
    private $dealerBusts;
    private $playerMove;
    private $blackjack;
    public $winner;

    function __construct() {
        $this->decksUsed = 6;
        $this->suit = [2,3,4,5,6,7,8,9,10,10,10,10,11];
        $this->deck = array_merge($this->suit, $this->suit, $this->suit, $this->suit);
        $this->shoe = array();
        $this->playerCards = array();
        $this->dealerCards = array();
        $this->winner = array();

        echo "---------------------------------------------" . PHP_EOL;
        $this->setupShoe();
        $this->dealInitialCards();
        $this->checkForBlackjack();
        if (!$this->blackjack) {
            $this->determinePlayersStrategy();
            $this->playTheHand();
        }
    }

    public function importStrategy() {
        $strategy = "bovada.csv";
        $this->strategyFile = fopen($strategy, "r");
    }

    public function setupShoe() {
        for ($i = 0; $i < $this->decksUsed; $i++) {
            $this->shoe = array_merge($this->shoe, $this->deck);
        }
        shuffle($this->shoe);
    }

    public function dealInitialCards() {
        $this->playerCard1 = array_shift($this->shoe);
        $this->dealerCard1 = array_shift($this->shoe);
        $this->playerCard2 = array_shift($this->shoe);
        $this->dealerCard2 = array_shift($this->shoe);

        //temp for split testing
        $this->playerCard1 = 11;
        $this->playerCard2 = 11;

        array_push($this->playerCards, $this->playerCard1, $this->playerCard2);
        array_push($this->dealerCards, $this->dealerCard1, $this->dealerCard2);

        echo "Player cards: " . $this->playerCard1 . ", " . $this->playerCard2 . PHP_EOL;
        echo "Dealer cards: " . $this->dealerCard1 . ", " . $this->dealerCard2 . PHP_EOL;

        $this->parsePlayersHand();

        if ($this->dealerCard2 == 11 && $this->dealerCard1 == 11) $this->dealerCard2 = 1;
        $this->dealerTotal = array_sum($this->dealerCards);
    }

    public function parsePlayersHand() {
        if ($this->playerCard1 == $this->playerCard2) {
            $this->playerTotal = $this->playerCard1 . $this->playerCard2;
        } else if ($position = array_search(11, $this->playerCards)) {
            unset($this->playerCards[$position]);
            $otherCard = array_shift($this->playerCards);
            $this->playerTotal = 11 . $otherCard;
        } else {
            $this->playerTotal = array_sum($this->playerCards);
        }
    }

    public function checkForBlackjack() {
        $this->blackjack = true;
        if ($this->playerTotal == 21 && $this->dealerTotal < 21) {
            $this->playerHitsBlackjack();
        } else if ($this->playerTotal == 21 && $this->dealerTotal == 21) {
            $this->playerTies();
        } else if ($this->dealerTotal == 21) {
            $this->dealerHitsBlackjack();
        } else {
            $this->blackjack = false;
        }
    }

    public function determinePlayersStrategy() {
        $this->importStrategy();
        $column = $this->dealerCard1 - 1;
        while ($row = fgetcsv($this->strategyFile)) {
            if ($row[0] == $this->playerTotal) {
                $this->playerMove = $row[$column];
                break;
            }
        }
    }

    public function playTheHand() {
        switch($this->playerMove) {
            case 'S':
                echo "Player's best move is to stand." . PHP_EOL;
                $this->playStand();
                break;
            case 'H':
                echo "Player's best move is to hit." . PHP_EOL;
                $this->playHit();
                break;
            case 'D':
                echo "Player's best move is to double." . PHP_EOL;
                $this->playDouble();
                break;
            case 'P':
                echo "Player's best move is to split." . PHP_EOL;
                $this->playSplit();
                break;
            case 'Sr':
                echo "Player's best move is to surrender." . PHP_EOL;
                $this->playerSurrenders();
                break;
        }
    }

    function playStand() {
        if ($this->dealerTotal < 17) {
            $this->dealerDraws();
        }
        if ($this->dealerTotal > 21) {
            $this->dealerBusts = true;
            $this->playerWins();
        } else if ($this->dealerTotal > $this->playerTotal) {
            $this->playerLooses();
        } else if ($this->dealerTotal < $this->playerTotal) {
            $this->playerWins();
        } else {
            $this->playerTies();
        }
    }

    function playHit() {
        $this->playerDraws();
        if ($this->playerTotal > 21) {
            $this->playerBusts = true;
            $this->playerLooses();
        } else {
            $this->playStand();
        }
    }

    function playDouble() {
        $this->playerDrawsOne();
        $this->playStand();
    }

    function playSplit() {
        if ($this->playerTotal == 1111) { //just for aces
            foreach ($this->playerCards as $card) {
                echo "----------" . PHP_EOL;
                $this->playerCards = [$card];
                $this->playerDrawsOne();
                $this->checkForBlackjack();
                if (!$this->blackjack) {
                    $this->playStand();
                }
            }
        } else { //any other pair
        }
    }

    function playerDraws() {
        while ($this->playerTotal <= 21) {
            $this->playerDrawsOne();
            $this->determinePlayersStrategy();
            if ($this->playerMove == "S") break;
        }
    }

    function playerDrawsOne() {
        $newCard = array_shift($this->shoe);
        echo "Player draws: " . $newCard . PHP_EOL;
        $this->playerCards[] = $newCard;
        $this->parsePlayersHand();
        $this->playerTotal = array_sum($this->playerCards);
    }

    function dealerDraws() {
        while ($this->dealerTotal <= 21) {
            $newCard = array_shift($this->shoe);
            echo "Dealer draws: " . $newCard . PHP_EOL;
            if ($newCard == 11 && $this->dealerTotal > 10) $newCard = 1;
            $this->dealerCards[] = $newCard;
            $this->dealerTotal = array_sum($this->dealerCards);
            if ($this->dealerTotal >= 17) break;
        }
    }

    function playerHitsBlackjack() {
        echo "Player wins! Player hits Blackjack!" . PHP_EOL;
        $this->winner[] = 0;
    }

    function dealerHitsBlackjack() {
        echo "Player looses. Dealer hit Blackjack." . PHP_EOL;
        $this->winner[] = 3;
    }

    function playerWins() {
        if ($this->dealerBusts) {
            echo "Player wins! Player: " . $this->playerTotal . ", Dealer busts: " . $this->dealerTotal . PHP_EOL;
            $this->winner[] = 5;
        } else {
            echo "Player wins! Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            $this->winner[] = 1;
        }
    }

    function playerLooses() {
        if ($this->playerBusts) {
            echo "Player busts. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            $this->winner[] = 2;
        } else {
            echo "Player looses. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            $this->winner[] = 4;
        }
    }

    function playerTies() {
        echo "Push. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
        $this->winner[] = 6;
    }

    function playerSurrenders() {
        echo "Player surrenders. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
        $this->winner[] = 7;
    }
}
