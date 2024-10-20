<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>{{title}}</title>
        <link rel="stylesheet" type="text/css" href="styles/on_game.css">
    </head>
    <body>
        {{{header}}}
        <div id="gameBoard">
            <div id="left">
                <div id="questionCard">
                    <div class="cardContent">
                        <a>{{card_to_complete}}</a>
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
                    <a>{{card1}}</a>
                </div>
            </li>
            <li class="card">
                <div class="cardContent">
                    <a>{{card2}}</a>
                </div>
            </li>
            <li class="card">
                <div class="cardContent">
                    <a>{{card3}}</a>
                </div>
            </li>
            <li class="card">
                <div class="cardContent">
                    <a>{{card4}}</a>
                </div>
            </li>
            <li class="card">
                <div class="cardContent">
                    <a>{{card5}}</a>
                </div>
            </li>
            <li class="card">
                <div class="cardContent">
                    <a>{{card6}}</a>
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

            fetch('{{first}}', {
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



        if (!window.location.href.includes("{{seconnd}}")) {
            window.location.replace("{{third}}")
        }

        let timeStamp = {{fourth}};
        let now = Date.now();
        let elapsedTime = now - timeStamp;
        let remainingTime = 30 - parseInt(`${elapsedTime / 1000}`)





        if (remainingTime <= 0) {
            const cards = {{{fifth}}};

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
                window.location.replace("{{sixth}}")
            }
        }, 1000)

        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            console.log(card.childNodes[1].childNodes[1].textContent)
            card.addEventListener('click', cardClicked);
        });

    </script>
</html>