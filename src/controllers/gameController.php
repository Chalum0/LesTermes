<?php

require('../vendor/autoload.php');
require_once('../src/utils/cards.php');

session_start();
$round_duration = 30;


// connect to db
$pdo = new PDO('mysql:host=localhost;dbname=lestermes', 'php', 'Starving6-Untamed2-Scant7');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$fluent = new Envms\FluentPDO\Query($pdo);


// give player a unique id
if (!isset($_GET['player_id'])){
    $player_id = uniqid();
} else {
    $player_id = $_GET['player_id'];
}

// collect cards
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // collect user data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['selected_card'])) {
        $selected_card = strval($data['selected_card']);

        $player = $fluent->from('players')
                 ->select('cards')
                 ->where('player_id', $player_id)
                 ->fetch();

        $player_cards = json_decode($player['cards'], true);
        $existing_submission = $fluent->from('submissions')
                              ->select('id')
                              ->where('player_id', $player_id)
                              ->fetch();

        if (in_array($selected_card, $player_cards)) {
            try {
                if ($existing_submission) {
                    $fluent->update('submissions')
                           ->set(['card_text' => $selected_card])
                           ->where('id', $existing_submission['id'])
                           ->execute();
                } else {
                    $fluent->insertInto('submissions')
                           ->values([
                               'player_id' => $player_id,
                               'card_text' => $selected_card
                           ])
                           ->execute();
                }
            
                echo json_encode(['success' => true]);
                exit();
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit();
            }
            
            echo json_encode(['success' => false, 'message' => 'Invalid card selection or no card selected.']);
            exit();
        }
    }
}

try {
    // Check if a game state exists
    $game_state = $fluent->from('game_state')
                         ->select('current_card, timestamp')
                         ->orderBy('id DESC')
                         ->limit(1)
                         ->fetch();

    if ($game_state) {
        $timestamp = $game_state['timestamp'];
        if (30 - (time() - $timestamp) <= -30) {
            try {
                $pdo->beginTransaction();
                
                // Reset game state and related data
                $pdo->exec("DELETE FROM game_state");
                $pdo->exec("DELETE FROM players");
                $pdo->exec("DELETE FROM submissions");


                $pdo->commit();

                // Insert new game state
                $card = $to_complete_cars[array_rand($to_complete_cars)];
                $timestamp = time();
                $fluent->insertInto('game_state')
                       ->values(['current_card' => $card, 'timestamp' => $timestamp])
                       ->execute();
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "Error resetting game: " . $e->getMessage();
            }
        } else {
            $card = $game_state['current_card'];
        }
    } else {
        // Insert initial game state if none exists
        $card = $to_complete_cars[array_rand($to_complete_cars)];
        $timestamp = time();
        $fluent->insertInto('game_state')
               ->values(['current_card' => $card, 'timestamp' => $timestamp])
               ->execute();
    }
} catch (PDOException $e) {
    echo "Error retrieving game state: " . $e->getMessage();
    exit();
}

try {
    // Fetch player data
    $player = $fluent->from('players')
                     ->select('cards')
                     ->where('player_id', $player_id)
                     ->fetch();

    if ($player) {
        // Player exists, decode their cards
        $player_cards = json_decode($player['cards'], true);
    } else {
        // Generate new cards for a new player
        $cards_keys = array_rand($completion_cards, 6);
        $player_cards = [];
        foreach ($cards_keys as $key) {
            $player_cards[] = $completion_cards[$key];
        }
        
        // Insert new player into the database
        $fluent->insertInto('players')
               ->values([
                   'player_id' => $player_id,
                   'cards' => json_encode($player_cards),
                   'last_active' => time()
               ])
               ->execute();
    }
} catch (PDOException $e) {
    echo "Error retrieving player data: " . $e->getMessage();
    exit();
};



$first_js = $_SERVER['PHP_SELF'] . '?player_id=' . $player_id;
$second_js = $player_id;
$third_js = $_SERVER['PHP_SELF'] . '?player_id=' . $player_id;
$fourth_js = $timestamp * 1000;
$submissions = $fluent->from('submissions')
                      ->select('card_text')
                      ->fetchAll(PDO::FETCH_COLUMN);
$fifth_js = !empty($submissions) ? json_encode($submissions) : '[]';
$sixth_js = $_SERVER['PHP_SELF'] . '?player_id=' . $player_id;

$mustache = new Mustache_Engine;

$template = file_get_contents('../src/templates/layout.php');
$header = file_get_contents('../src/templates/partials/_nav.php');
$data = [
    'title' => 'Les termes - On Game',
    'header' => $header,
    'card_to_complete' => $card,
    'card1' => $player_cards[0],
    'card2' => $player_cards[1],
    'card3' => $player_cards[2],
    'card4' => $player_cards[3],
    'card5' => $player_cards[4],
    'card6' => $player_cards[5],
    'first' => $first_js,
    'second' => $second_js,
    'third' => $third_js,
    'fourth' => $fourth_js,
    'fifth' => $fifth_js,
    'sixth' => $sixth_js
];

echo $mustache->render($template, $data);
