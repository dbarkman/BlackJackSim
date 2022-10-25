<?php

/**
 * bjSimNew.php
 * Project: bjSim
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 10/22/22 @ 15:12
 */

$hands = $argv[1];

$bj = new BlackJack($hands);
$bj->play();

class BlackJack
{
    private $decksUsed;
    private $suit;
    private $deck;
    private $shoe;
    private $hands;
    private $playerHands;
    private $playerCards;
    private $playerTotal;
    private $handTotals;
    private $dealerCards;
    private $dealerCard1;
    private $dealerTotal;
    private $playerMove;
    private $aces;
    private $splitCounter;
    private $verbose;

    function __construct($hands) {
        $this->decksUsed = 1;
        $this->suit = [2,3,4,5,6,7,8,9,10,10,10,10,11];
        $this->deck = array_merge($this->suit, $this->suit, $this->suit, $this->suit);
        $this->shoe = array();
        $this->hands = $hands;
        $this->playerHands = array();
        $this->playerCards = array();
        $this->handTotals = array();
        $this->dealerCards = array();

        $this->verbose = true;
    }

    public function play() {
        $this->setupShoe();
        $this->dealCards();
        $this->playHands();
        $this->evaluateHands();
    }
    
    private function setupShoe() {
        for ($i = 0; $i < $this->decksUsed; $i++) {
            $this->shoe = array_merge($this->shoe, $this->deck);
        }
        shuffle($this->shoe);
        array_unshift($this->shoe, 2,8,2,10);
    }
    
    private function dealCards() {
        for ($i = 0; $i < $this->hands; $i++) {
            $this->playerHands[$i] = [array_shift($this->shoe)];
        }
        $this->dealerCard1 = array_shift($this->shoe);
        for ($i = 0; $i < $this->hands; $i++) {
            $hand = $this->playerHands[$i];
            $hand[] = array_shift($this->shoe);
            $this->playerHands[$i] = $hand;
        }
        $dealerCard2 = array_shift($this->shoe);
        array_push($this->dealerCards, $this->dealerCard1, $dealerCard2);

        foreach ($this->playerHands as $hand) {
            $this->out("Player cards: " . $hand[0] . ", " . $hand[1]);
        }
        $this->out("Dealer cards: " . $this->dealerCard1 . ", " . $dealerCard2);

        if ($dealerCard2 == 11 && $this->dealerCard1 == 11) $dealerCard2 = 1;
        $this->dealerTotal = array_sum($this->dealerCards);
    }
    
    private function playHands() {
        foreach ($this->playerHands as &$hand) {
            echo "----------" . PHP_EOL;
            //clean up hand need a sum or ordered cards
            if (count($hand) < 2) {
                $newCard = array_shift($this->shoe);
                $this->out("Player draws: " . $newCard . ", for " . $this->playerTotal);
                $hand[] = $newCard;
            }
            $this->playerCards = $hand;
            $this->cleanupHand();

            if ($this->playerTotal == 21) {
                echo "BLACKJACK!" . PHP_EOL;
            }

            //player decides strategy, needs playerTotal
            $this->determineStrategy();

            //player plays
            $this->playHand();
        } //end of loop

        //dealer plays
        echo "----------" . PHP_EOL;
        if ($this->dealerTotal == 22) {
            unset($this->dealerCards[1]);
            $this->dealerCards[1] = 1;
            $this->dealerTotal = array_sum($this->dealerCards);
        }
        if ($this->dealerTotal <= 17) {
            $this->dealerDraws();
        }
        echo "Dealer stands with: " . $this->dealerTotal . PHP_EOL;
    }

    private function playHand() {
        //stand
        if ($this->playerMove == "S") {
            if ($this->playerTotal > 0) {
                echo "Player will stand with: " . $this->playerTotal . PHP_EOL;
            }
            $this->handTotals[] = $this->playerTotal;

        } else if ($this->playerMove == "H") {
            echo "Player will hit with: " . $this->playerTotal . PHP_EOL;
            while ($this->playerTotal <= 21) {
                $newCard = array_shift($this->shoe);
                $originalNewCard = $newCard;
                if ($newCard == 11 && $this->playerTotal > 10) $newCard = 1;
                $this->playerCards[] = $newCard;
                $this->playerTotal = array_sum($this->playerCards);
                $this->out("Player draws: " . $originalNewCard . ", for " . $this->playerTotal);
                if ($this->playerTotal > 21) {
                    $position = array_search(11, $this->playerCards);
                    if ($position !== FALSE) {
                        unset($this->playerCards[$position]);
                        $this->playerCards[$position] = 1;
                        $this->playerTotal = array_sum($this->playerCards);
                        $this->determineStrategy();
                        if ($this->playerMove == "S") break;
                    } else {
                        $this->playerMove = "S";
//                        $this->playHand();
                    }
                } else {
                    $this->determineStrategy();
                    if ($this->playerMove == "S") break;
//                    $this->playHand();
                }
            }
            $this->playHand();

//////////////////////////
//            echo "Player will hit with: " . $this->playerTotal . PHP_EOL;
//            while ($this->playerTotal <= 21) {
//                $newCard = array_shift($this->shoe);
//                $this->outputToTerminal("Player draws: " . $newCard);
//                if ($newCard == 11 && $this->playerTotal > 10) $newCard = 1;
//                $this->playerCards[] = $newCard;
//                $this->playerTotal = array_sum($this->playerCards);
//                if ($this->playerTotal > 21) {
//                    $this->playerMove = "S";
////                    $this->playHand();
//                } else {
//                    $this->determineStrategy();
//                    if ($this->playerMove == "S") break;
////                    $this->playHand();
//                }
//            }
//            $this->playHand();
//////////////////////////

        } else if ($this->playerMove == "D") {
            echo "Player will double down with: " . $this->playerTotal . PHP_EOL;
            $newCard = array_shift($this->shoe);
            $originalNewCard = $newCard;
            if ($newCard == 11 && $this->playerTotal > 10) $newCard = 1;
            $this->playerCards[] = $newCard;
            $this->playerTotal = array_sum($this->playerCards);
            $this->out("Player draws: " . $originalNewCard . ", for " . $this->playerTotal);
            $this->playerMove = "S";
            $this->playHand();

        } else if ($this->playerMove == "P") {
            echo "Player will split with: " . $this->playerTotal . PHP_EOL;
            if ($this->playerTotal == 22) { //just for aces
                echo "Splitting Aces" . PHP_EOL;
                foreach ($this->playerCards as $card) {
                    $newCard = array_shift($this->shoe);
                    $originalNewCard = $newCard;
                    if ($newCard == 11) $newCard = 1;
                    $this->playerCards = [$card, $newCard];
                    $this->playerTotal = array_sum($this->playerCards);
                    $this->out("Player draws: " . $originalNewCard . ", for " . $this->playerTotal);
                    $this->playerMove = "S";
                    $this->playHand();
                }
            } else {
                $this->splitCounter++;
                if ($this->splitCounter < 3) {
                    $secondCard = $this->playerCards[1];
                    echo "Splitting " . $secondCard . "s" . PHP_EOL;
                    unset($this->playerCards[1]);
                    $newCard = array_shift($this->shoe);
                    $this->playerCards[1] = $newCard;
                    $this->out("Player draws: " . $newCard . ", for " . ($secondCard + $newCard));
                    $this->playerHands[] = [$secondCard];
                }
                //clean up hand
                $this->cleanupHand();
                //determine strategy
                $this->determineStrategy();
                $this->playHand();
            }

        } else if ($this->playerMove == "Sr" && count($this->playerCards) == 2) {
            echo "Player will surrender with: " . $this->playerTotal . ", against: " . $this->dealerCard1 . PHP_EOL;
            $this->playerTotal = 0;
            $this->playerMove = "S";
            $this->playHand();

        } else if ($this->playerMove == "Sr" && count($this->playerCards) > 2) {
            if ($this->playerTotal < 17) {
                $this->playerMove = "H";
            } else {
                $this->playerMove = "S";
            }
            $this->playHand();
        }
    }

    private function cleanupHand() {
        $position = array_search(11, $this->playerCards);
        if ($this->playerCards[0] == $this->playerCards[1] && $this->splitCounter < 3) {
            if ($this->playerCards[0] == 11) $this->aces = true;
            $this->playerTotal = $this->playerCards[0] . $this->playerCards[1];
        } else if ($position !== FALSE && $this->splitCounter < 3) {
            unset($this->playerCards[$position]);
            $otherCard = array_shift($this->playerCards);
            $this->playerTotal = 11 . $otherCard;
            array_push($this->playerCards, 11, $otherCard);
        } else {
            $this->playerTotal = array_sum($this->playerCards);
        }
        if ($this->playerTotal == 22 && $this->aces) {
            $this->playerCards[1] = 1;
            $this->playerTotal = array_sum($this->playerCards);
        }
    }

    private function determineStrategy() {
        $this->importStrategy();
        $column = $this->dealerCard1 - 1;
        while ($row = fgetcsv($this->strategyFile)) {
            if ($row[0] == $this->playerTotal) {
                $this->playerMove = $row[$column];
                break;
            }
        }
        $this->playerTotal = array_sum($this->playerCards);
    }

    private function dealerDraws() {
        while ($this->dealerTotal <= 21) {
            if (!in_array(11, $this->dealerCards) && $this->dealerTotal >= 17) break;
            $newCard = array_shift($this->shoe);
            $dealerDraw = "Dealer draws: " . $newCard;
            if ($newCard == 11 && $this->dealerTotal > 10) $newCard = 1;
            $this->dealerCards[] = $newCard;
            $this->dealerTotal = array_sum($this->dealerCards);
            $this->out($dealerDraw . ", for: " . $this->dealerTotal);
            if (in_array(11, $this->dealerCards) && $this->dealerTotal > 17) break;
        }
    }

    private function evaluateHands() {
        echo "----------" . PHP_EOL;
        foreach ($this->handTotals as $handTotal) {
            if ($handTotal > 21) {
                echo "Player looses, player busts. Player: " . $handTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            } else if ($handTotal == 0) {
                echo "Player surrenders." . PHP_EOL;
            } else if ($this->dealerTotal > 21) {
                echo "Player wins, dealer busts! Player: " . $handTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            } else if ($this->dealerTotal > $handTotal) {
                echo "Player looses. Player: " . $handTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            } else if ($handTotal > $this->dealerTotal) {
                echo "Player wins! Player: " . $handTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            } else {
                echo "Push Player: " . $handTotal . ", Dealer: " . $this->dealerTotal . PHP_EOL;
            }
        }
    }

    private function importStrategy() {
        $strategy = "bovada.csv";
        $this->strategyFile = fopen($strategy, "r");
    }

    function out($output) {
        if ($this->verbose) {
            echo $output . PHP_EOL;
        }
    }
}