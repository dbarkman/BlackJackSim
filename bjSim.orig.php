<?php

/*
 * To do:
 * Write test file
 * Test Limit how many splits
 * money
 */

if (count($argv) < 3) {
    echo "include argument for iterations and hands" . PHP_EOL;
} else {
    $iterations = $argv[1];
    $hands = $argv[2];

    $playerBlackjack = 0;
    $playerWins = 0;
    $playerBusts = 0;
    $dealerBlackjack = 0;
    $dealerWins = 0;
    $dealerBusts = 0;
    $push = 0;
    $surrender = 0;
    $stand = 0;
    $hit = 0;
    $double = 0;
    $split = 0;
    $run = 0;

    $test = isset($argv[3]) ? $argv[3] : false;
    $testShoe = isset($argv[4]) ? explode(',',$argv[4]) : [];
    $expectedStrategy = isset($argv[5]) ? $argv[5] : 'S';
    $expectedOutcome = isset($argv[6]) ? $argv[6] : 0;

    for ($i = 0; $i < $iterations; $i++) {
        $bj = new BlackJack($hands, $test, $testShoe, $expectedStrategy, $expectedOutcome);
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
        $strategies = $bj->strategies;
        $stand += $strategies['S'];
        $hit += $strategies['H'];
        $double += $strategies['D'];
        $split += $strategies['P'];
        $run += $strategies['R'];
    }

    if (!$test) {
        echo PHP_EOL;
        echo "------------------------------------------------------------------------------------------" . PHP_EOL;
        echo PHP_EOL;
        echo "Results for " . $iterations . " games:" . PHP_EOL;
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
        echo "Stand: " . $stand . " - " . ($stand / $iterations * 100) . "%" . PHP_EOL;
        echo "Hit: " . $hit . " - " . ($hit / $iterations * 100) . "%" . PHP_EOL;
        echo "Double: " . $double . " - " . ($double / $iterations * 100) . "%" . PHP_EOL;
        echo "Split: " . $split . " - " . ($split / $iterations * 100) . "%" . PHP_EOL;
        echo "Surrender: " . $run . " - " . ($run / $iterations * 100) . "%" . PHP_EOL;
        echo PHP_EOL;
    }
}

class BlackJack
{
    private $test;
    private $testShoe;
    private $expectedStrategy;
    private $expectedOutcome;

    private $strategyFile;
    private $decksUsed;
    private $suit;
    private $deck;
    private $shoe;
    private $hands;
    private $playerHands;
    private $playerCards;//needs to hold multiple for splits
    private $dealerCards;
    private $dealerCard1;
    private $dealerCard2;
    private $playerTotal;//needs to hold multiple for splits
    private $handTotals;
    private $dealerTotal;
    private $playerBusts;//needs to hold multiple for splits
    private $dealerBusts;
    private $playerMove;
    private $blackjack;
    private $splitCounter;
    private $verbose;
    public $winner;
    public $strategies;

    function __construct($hands, $test, $testShoe, $expectedStrategy, $expectedOutcome) {
        $this->decksUsed = 6;
        $this->suit = [2,3,4,5,6,7,8,9,10,10,10,10,11];
        $this->deck = array_merge($this->suit, $this->suit, $this->suit, $this->suit);
        $this->shoe = array();
        $this->hands = $hands;
        $this->playerHands = array();
        $this->playerCards = array();
        $this->dealerCards = array();
        $this->winner = array();
        $this->strategies = ['S' => 0, 'H' => 0, 'D' => 0, 'P' => 0, 'R' => 0];

        $this->verbose = true;

        if ($test) {
            $this->test = $test;
            $this->testShoe = $testShoe;
            $this->expectedStrategy = $expectedStrategy;
            $this->expectedOutcome = $expectedOutcome;
        }

        $this->outputToTerminal("---------------------------------------------");

        $this->setupShoe();
        $this->dealInitialCards();

        foreach ($this->playerHands as $hand) {
            echo "----------" . PHP_EOL;
            $this->playerCards = [$hand[0], $hand[1]];
            $this->parsePlayersHand();
            $this->determinePlayersStrategy();
            $playerMove = $this->playerMove;
            $this->checkForBlackjack();
            if (!$this->blackjack) {
                $this->playTheHand();
            }
            echo "----------" . PHP_EOL;
        }
//        exit();

        if ($this->test) $this->checkExpectedStrategy($this->expectedStrategy, $playerMove);
        if ($this->test) $this->checkExpectedOutcome($this->expectedOutcome, $this->winner[0]);
    }

    private function checkExpectedStrategy($expected, $actual) {
        if ($expected == $actual) {
            echo "Strategy: PASS" . PHP_EOL;
        } else {
            echo "Strategy: FAIL" . PHP_EOL;
        }
        echo "Expected: " . $expected . ", Actual: " . $actual . PHP_EOL;
    }

    private function checkExpectedOutcome($expected, $actual) {
        if ($expected == $actual) {
            echo "Outcome: PASS" . PHP_EOL;
        } else {
            echo "Outcome: FAIL" . PHP_EOL;
        }
        echo "Expected: " . $expected . ", Actual: " . $actual . PHP_EOL;
    }

    private function importStrategy() {
        $strategy = "bovada.csv";
        $this->strategyFile = fopen($strategy, "r");
    }

    private function setupShoe() {
        for ($i = 0; $i < $this->decksUsed; $i++) {
            $this->shoe = array_merge($this->shoe, $this->deck);
        }
        shuffle($this->shoe);

        if ($this->test) {
            $this->shoe = $this->testShoe;
        }
    }

    private function dealInitialCards() {
        for ($i = 0; $i < $this->hands; $i++) {
            $this->playerHands[$i] = [array_shift($this->shoe)];
        }
        $this->dealerCard1 = array_shift($this->shoe);
        for ($i = 0; $i < $this->hands; $i++) {
            $hand = $this->playerHands[$i];
            $hand[] = array_shift($this->shoe);
            $this->playerHands[$i] = $hand;
        }
        $this->dealerCard2 = array_shift($this->shoe);

        array_push($this->dealerCards, $this->dealerCard1, $this->dealerCard2);

        foreach ($this->playerHands as $hand) {
            $this->outputToTerminal("Player cards: " . $hand[0] . ", " . $hand[1]);
        }
        $this->outputToTerminal("Dealer cards: " . $this->dealerCard1 . ", " . $this->dealerCard2);

        if ($this->dealerCard2 == 11 && $this->dealerCard1 == 11) $this->dealerCard2 = 1;
        $this->dealerTotal = array_sum($this->dealerCards);
    }

    private function parsePlayersHand() {
        $this->playerTotal = 0;
        $position = array_search(11, $this->playerCards);
        if ($this->playerCards[0] == $this->playerCards[1]) {
            $this->playerTotal = $this->playerCards[0] . $this->playerCards[1];
        } else if ($position !== FALSE) {
            unset($this->playerCards[$position]);
            $otherCard = array_shift($this->playerCards);
            $this->playerTotal = 11 . $otherCard;
            array_push($this->playerCards, 11, $otherCard);
        } else {
            $this->playerTotal = array_sum($this->playerCards);
        }
    }

    private function checkForBlackjack($checkDealer = true) {
        $this->blackjack = false;
        if ($this->playerTotal == 21 && $this->dealerTotal != 21) {
            $this->blackjack = true;
            $this->playerHitsBlackjack();
        } else if ($this->playerTotal == 21 && $this->dealerTotal == 21) {
            $this->blackjack = true;
            $this->playerTies();
        } else if ($this->playerTotal != 21 && $this->dealerTotal == 21 && $checkDealer) {
            $this->blackjack = true;
            $this->dealerHitsBlackjack();
        }
    }

    private function determinePlayersStrategy() {
        $this->importStrategy();
        $column = $this->dealerCard1 - 1;
        while ($row = fgetcsv($this->strategyFile)) {
            if ($row[0] == $this->playerTotal) {
                $this->playerMove = $row[$column];
                break;
            }
        }
        $this->playerTotal = array_sum($this->playerCards);
//        echo "Player's best move is: " . $this->playerMove . " - pt: " . $this->playerTotal . PHP_EOL;
//        if ($this->playerMove == 'P') { // && $this->splitCounter > 2) {
//            $this->determinePlayersStrategy();
//        }
    }

    private function playTheHand() {
        switch($this->playerMove) {
            case 'S':
                $this->strategies['S']++;
                $this->outputToTerminal("Player's best move is to stand.");
                $this->handTotals[] = $this->playerTotal;
//                $this->playStand();
                break;
            case 'H':
                $this->strategies['H']++;
                $this->outputToTerminal("Player's best move is to hit.");
                $this->playerDraws();
                $this->handTotals[] = $this->playerTotal;
//                $this->playHit();
                break;
            case 'D':
                $this->strategies['D']++;
                $this->outputToTerminal("Player's best move is to double.");
                $this->playerDrawsOne();
                $this->handTotals[] = $this->playerTotal;
//                $this->playDouble();
                break;
            case 'P':
                $this->splitCounter++;
                $this->strategies['P']++;
                $this->outputToTerminal("Player's best move is to split.");
                $this->playSplit();
                break;
            case 'Sr':
                $this->strategies['R']++;
                $this->outputToTerminal("Player's best move is to surrender.");
                $this->handTotals[] = 0;
//                $this->playerSurrenders();
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
        $this->playerTotal = array_sum($this->playerCards);
        $this->playStand();
    }

    function playSplit() {
        if ($this->playerTotal == 22) { //just for aces
            foreach ($this->playerCards as $card) {
                $this->outputToTerminal("----------");
                $this->playerCards = [$card];
                $this->playerDrawsOne(true);
                $this->checkForBlackjack(false);
                if (!$this->blackjack) {
                    $this->playStand();
                }
            }
        } else { //any other pair
            foreach ($this->playerCards as $card) {
                $this->outputToTerminal("----------");
                $this->playerCards = array($card);
                $this->playerDrawsOne();
                $this->determinePlayersStrategy();
                $this->checkForBlackjack(false);
                if (!$this->blackjack) {
                    $this->playTheHand();
                }
            }
        }
    }

    function playerDraws() {
        while ($this->playerTotal <= 21) {
            $this->playerDrawsOne();
            $this->determinePlayersStrategy();
            if ($this->playerMove == "S") break;
        }
    }

    function playerDrawsOne($aces = false) {
        $newCard = array_shift($this->shoe);
        $this->outputToTerminal("Player draws: " . $newCard);
        if ($aces) if ($newCard == 11) $newCard = 1;
        $this->playerCards[] = $newCard;
        $this->parsePlayersHand();
    }

    function dealerDraws() {
        while ($this->dealerTotal <= 21) {
            $newCard = array_shift($this->shoe);
            $this->outputToTerminal("Dealer draws: " . $newCard);
            if ($newCard == 11 && $this->dealerTotal > 10) $newCard = 1;
            $this->dealerCards[] = $newCard;
            $this->dealerTotal = array_sum($this->dealerCards);
            if (in_array(11, $this->dealerCards) && $this->dealerTotal > 17) break;
            if (!in_array(11, $this->dealerCards) && $this->dealerTotal >= 17) break;
        }
    }

    function playerHitsBlackjack() {
        $this->outputToTerminal("Player wins! Player hits Blackjack!");
        $this->winner[] = 0;
    }

    function dealerHitsBlackjack() {
        $this->outputToTerminal("Player looses. Dealer hit Blackjack.");
        $this->winner[] = 3;
    }

    function playerWins() {
        if ($this->dealerBusts) {
            $this->outputToTerminal("Player wins! Player: " . $this->playerTotal . ", Dealer busts: " . $this->dealerTotal);
            $this->winner[] = 5;
        } else {
            $this->outputToTerminal("Player wins! Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal);
            $this->winner[] = 1;
        }
    }

    function playerLooses() {
        if ($this->playerBusts) {
            $this->outputToTerminal("Player busts. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal);
            $this->winner[] = 2;
        } else {
            $this->outputToTerminal("Player looses. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal);
            $this->winner[] = 4;
        }
    }

    function playerTies() {
        $this->outputToTerminal("Push. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal);
        $this->winner[] = 6;
    }

    function playerSurrenders() {
        $this->outputToTerminal("Player surrenders. Player: " . $this->playerTotal . ", Dealer: " . $this->dealerTotal);
        $this->winner[] = 7;
    }

    function outputToTerminal($output) {
        if ($this->verbose) {
            echo $output . PHP_EOL;
        }
    }
}
