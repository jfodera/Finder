let targetColor = { r: 0, g: 0, b: 0 };
let gameStarted = false;
let timer;
const gameDuration = 10; // seconds

document.getElementById('startButton').addEventListener('click', startGame);
document.getElementById('submitButton').addEventListener('click', calculateScore);
document.getElementById('restartButton').addEventListener('click', restartGame);

function startGame() {
    targetColor = {
        r: Math.floor(Math.random() * 256),
        g: Math.floor(Math.random() * 256),
        b: Math.floor(Math.random() * 256),
    };
    
    document.getElementById('colorDisplay').style.backgroundColor = `rgb(${targetColor.r}, ${targetColor.g}, ${targetColor.b})`;
    document.getElementById('startButton').disabled = true;
    document.getElementById('sliders').classList.remove('hidden');
    document.getElementById('scoreDisplay').innerText = '';
    document.getElementById('restartButton').classList.add('hidden');

    timer = setTimeout(() => {
        calculateScore();
    }, gameDuration * 1000);
    
    gameStarted = true;
}

function calculateScore() {
    if (!gameStarted) return;

    const red = parseInt(document.getElementById('redSlider').value);
    const green = parseInt(document.getElementById('greenSlider').value);
    const blue = parseInt(document.getElementById('blueSlider').value);

    const score = 255 - (Math.abs(red - targetColor.r) + Math.abs(green - targetColor.g) + Math.abs(blue - targetColor.b));
    
    document.getElementById('scoreDisplay').innerText = `Your Score: ${score}`;
    clearTimeout(timer);
    gameStarted = false;

    document.getElementById('startButton').disabled = false;
    document.getElementById('restartButton').classList.remove('hidden');
}

function restartGame() {
    document.getElementById('sliders').classList.add('hidden');
    document.getElementById('startButton').disabled = false;
    document.getElementById('scoreDisplay').innerText = '';
}
