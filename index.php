<?php
$to_complete_cars = [
    "Mon super pouvoir, c’est ____.",
    "Le plus gros scandale politique de l’année implique ____.",
    "Pendant mon temps libre, j'aime me déguiser en ____.",
    "Si je pouvais changer une seule chose dans le monde, ce serait ____.",
    "L’invention du siècle, c’est ____.",
    "Rien ne me fait plus peur que ____.",
    "Si je pouvais dîner avec une personne célèbre, je choisirais ____.",
    "Mon médecin m'a prescrit ____ pour calmer mes nerfs.",
    "Le prochain projet de Netflix s’appelle ____.",
    "J'ai été expulsé de l'école parce que j'ai apporté ____.",
    "Si les aliens nous attaquent, notre seule défense sera ____.",
    "Ma nouvelle stratégie pour attirer l’attention sur Tinder : ____.",
    "Le secret de la longévité, c’est ____.",
    "Les vacances de rêve pour moi, ce serait de passer une semaine à ____.",
    "Dans 50 ans, les historiens se souviendront de 2024 comme l'année de ____.",
    "La recette secrète de ma grand-mère contient un ingrédient spécial : ____.",
    "La seule chose qui pourrait améliorer cette fête, c'est ____.",
    "Le prochain film Marvel s'appellera ‘Avengers : ____’.",
    "Mon plan infaillible pour conquérir le monde commence par ____."
];
$completion_cards = [
    "Un poney rose en colère.",
    "Un karaoké avec des chèvres.",
    "Une invasion de canards en plastique.",
    "L'odeur du vieux fromage.",
    "Des chaussettes sales sous l’oreiller.",
    "Un rendez-vous romantique à Ikea.",
    "Le retour des jeans taille basse.",
    "Manger une pizza froide à 3h du matin.",
    "Les conseils beauté de Vladimir Poutine.",
    "Un iguane qui fait du yoga.",
    "La danse de la pluie sous MDMA.",
    "Des toilettes publiques au milieu de la jungle.",
    "Un jacuzzi rempli de soupe.",
    "Mon ex qui fait la une des journaux.",
    "Les plans secrets de la NASA pour coloniser Disneyland.",
    "Une séance de méditation dirigée par un chat.",
    "Des croquettes bio pour humains.",
    "Un orgasme provoqué par une feuille d’arbre.",
    "Le retour des dinosaures.",
    "Une rave dans une boulangerie."
];
?>


<?php
session_start();
$round_duration = 30;
try {
    $db = new PDO('sqlite:game.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Database Connection Error: " . $e->getMessage();
    exit();
}

if (!isset($_GET['player_id'])){
    $player_id = uniqid();
} else {
    $player_id = $_GET['player_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (isset($data['selected_card'])) {
        $selected_card = strval($data['selected_card']);

        $stmt = $db->prepare("SELECT cards FROM players WHERE player_id = :player_id");
        $stmt->execute([':player_id' => $player_id]);
        $player = $stmt->fetch(PDO::FETCH_ASSOC);
        $player_cards = json_decode($player['cards'], true);

        $stmt = $db->prepare("SELECT id FROM submissions WHERE player_id = :player_id");
        $stmt->execute([
            ':player_id' => $player_id,
        ]);
        $existing_submission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (in_array($selected_card, $player_cards)) {
            try {
                if ($existing_submission) {
                    $stmt = $db->prepare("UPDATE submissions SET card_text = :card_text WHERE id = :id");
                    $stmt->execute([
                        ':card_text' => $selected_card,
                        ':id' => $existing_submission['id']
                    ]);
                }else {
                    $stmt = $db->prepare("INSERT INTO submissions (player_id, card_text) VALUES (:player_id, :card_text)");
                    $stmt->execute([
                        ':player_id' => $player_id,
                        ':card_text' => $selected_card
                    ]);
                }

                echo json_encode(['success' => true]);
                exit();
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid card selection.']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No card selected.']);
        exit();
    }
}



try {
    // Check if a game state exists
    $stmt = $db->query("SELECT current_card, timestamp FROM game_state ORDER BY id DESC LIMIT 1");
    $game_state = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($game_state) {
        $timestamp = $game_state['timestamp'];
        if (30-(time()-$timestamp) <= -30) {
            try {
                $db->beginTransaction();
                $db->exec("DELETE FROM game_state");
                $db->exec("DELETE FROM players");
                $db->exec("DELETE FROM submissions");
                $db->commit();
                $card = $to_complete_cars[array_rand($to_complete_cars)];
                $timestamp = time();
                $stmt = $db->prepare("INSERT INTO game_state (current_card, timestamp) VALUES (:current_card, :timestamp)");
                $stmt->execute([':current_card' => $card, ':timestamp' => $timestamp]);
            } catch (PDOException $e) {
                $db->rollBack();
                echo "Error resetting game: " . $e->getMessage();
            }
        } else {
            $card = $game_state['current_card'];
        }


    } else {
        $card = $to_complete_cars[array_rand($to_complete_cars)];
        $timestamp = time();
        $stmt = $db->prepare("INSERT INTO game_state (current_card, timestamp) VALUES (:current_card, :timestamp)");
        $stmt->execute([':current_card' => $card, ':timestamp' => $timestamp]);
    }
} catch (PDOException $e) {
    echo "Error retrieving game state: " . $e->getMessage();
    exit();
}

try {
    $stmt = $db->prepare("SELECT cards FROM players WHERE player_id = :player_id");
    $stmt->execute([':player_id' => $player_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($player) {
        // Player exists, get their cards
        $player_cards = json_decode($player['cards'], true);
    } else {
        // New player, generate cards
        $cards_keys = array_rand($completion_cards, 6);
        $player_cards = [];
        foreach ($cards_keys as $key) {
            $player_cards[] = $completion_cards[$key];
        }
        // Insert player into database
        $stmt = $db->prepare("INSERT INTO players (player_id, cards, last_active) VALUES (:player_id, :cards, :last_active)");
        $stmt->execute([
            ':player_id' => $player_id,
            ':cards' => json_encode($player_cards),
            ':last_active' => time()
        ]);
    }
} catch (PDOException $e) {
    echo "Error retrieving player data: " . $e->getMessage();
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Les termes</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<nav>
    <ul>
        <li></li>
        <li>
            <p>Les Termes</p>
        </li>
        <li>
            <p id="timer">Timer:</p>
        </li>
    </ul>
</nav>
<div id="gameBoard">
    <div id="left">
        <div id="questionCard">
            <div class="cardContent">
                <a><?php echo $card ?></a>
            </div>
        </div>
    </div>
    <div id="right">
        <div id="answerCard">
            <p>◊</p>
        </div>
    </div>
</div>
<ul id="deck">
    <li class="card">
        <div class="cardContent">
            <a><?php echo $player_cards[0] ?></a>
        </div>
    </li>
    <li class="card">
        <div class="cardContent">
            <a><?php echo $player_cards[1] ?></a>
        </div>
    </li>
    <li class="card">
        <div class="cardContent">
            <a><?php echo $player_cards[2] ?></a>
        </div>
    </li>
    <li class="card">
        <div class="cardContent">
            <a><?php echo $player_cards[3] ?></a>
        </div>
    </li>
    <li class="card">
        <div class="cardContent">
            <a><?php echo $player_cards[4] ?></a>
        </div>
    </li>
    <li class="card">
        <div class="cardContent">
            <a><?php echo $player_cards[5] ?></a>
        </div>
    </li>
</ul>
</body>
<script>
    function cardClicked(event) {
        const cardText = event.currentTarget.querySelector('a').textContent;
        console.log(cardText)

        cards.forEach(card => {
            card.style.transform = "scale(1)";
        });
        event.currentTarget.style.transform = "translateY(-20px) scale(1.05)";

        fetch('<?php echo $_SERVER['PHP_SELF'] . '?player_id=' . $player_id; ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                selected_card: cardText
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch((error) => {
                console.error('Error:', error);
            });


    }



    if (!window.location.href.includes("<?php echo $player_id?>")) {
        window.location.replace("<?php echo $_SERVER['PHP_SELF'] . '?player_id=' . $player_id; ?>")
    }

    let timeStamp = <?php echo $timestamp * 1000; ?>;
    let now = Date.now();
    let elapsedTime = now - timeStamp;
    let remainingTime = 30 - parseInt(`${elapsedTime / 1000}`)





    if (remainingTime <= 0) {
        const cards = <?php
            $stmt = $db->prepare("SELECT card_text FROM submissions");
            $stmt->execute();
            $submissions = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            echo json_encode($submissions)?>;

        document.body.innerHTML = '';

        const newDiv = document.createElement('div');
        newDiv.id = 'answers_board';

        const list = document.createElement('ul');

        newDiv.appendChild(list);

        document.body.appendChild(newDiv);


        cards.forEach((itemText, index) => {
            setTimeout(() => {
                const listItem = document.createElement('li');
                listItem.className = "revealedCard"
                listItem.textContent = itemText;
                list.appendChild(listItem);
            }, index * 1000);
        });

        document.body.appendChild(newDiv);
    }
    else {
        document.querySelector("#timer").textContent = `Timer: ${remainingTime}`
    }





    setInterval(() => {
        remainingTime--
        console.log(remainingTime)
        if (remainingTime >= 0){
            document.querySelector("#timer").textContent = `Timer: ${remainingTime}`
        }
        if (remainingTime === 0 || remainingTime <= -30){
            window.location.replace("<?php echo $_SERVER['PHP_SELF'] . '?player_id=' . $player_id; ?>")
        }
    }, 1000)

    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        console.log(card.childNodes[1].childNodes[1].textContent)
        card.addEventListener('click', cardClicked);
    });

</script>
</html>

<?php



?>
