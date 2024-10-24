let targetColor = { r: 0, g: 0, b: 0 };
let gameStarted = false;
let timer;
let countdown;
let gameMode = 'normal';
let gameDuration = 10; // seconds
let maxScore = 100;
let timeRemaining;

const redSlider = document.getElementById('redSlider');
const greenSlider = document.getElementById('greenSlider');
const blueSlider = document.getElementById('blueSlider');
const colorPreview = document.getElementById('colorPreview');
const easyModeBtn = document.getElementById('easyMode');
const normalModeBtn = document.getElementById('normalMode');
const hardModeBtn = document.getElementById('hardMode');

document.getElementById('startButton').addEventListener('click', startGame);
document.getElementById('submitButton').addEventListener('click', calculateScore);
document.getElementById('restartButton').addEventListener('click', restartGame);

easyModeBtn.addEventListener('click', () => setGameMode('easy'));
normalModeBtn.addEventListener('click', () => setGameMode('normal'));
hardModeBtn.addEventListener('click', () => setGameMode('hard'));

redSlider.addEventListener('input', updateColorPreview);
greenSlider.addEventListener('input', updateColorPreview);
blueSlider.addEventListener('input', updateColorPreview);

function setGameMode(mode) {
    gameMode = mode;
    easyModeBtn.classList.remove('active');
    normalModeBtn.classList.remove('active');
    hardModeBtn.classList.remove('active');

    switch (mode) {
        case 'easy':
            easyModeBtn.classList.add('active');
            gameDuration = 15;
            maxScore = 100;
            break;
        case 'normal':
            normalModeBtn.classList.add('active');
            gameDuration = 10;
            maxScore = 100;
            break;
        case 'hard':
            hardModeBtn.classList.add('active');
            gameDuration = 7;
            maxScore = 150;
            break;
    }
}

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

    timeRemaining = gameDuration;
    document.getElementById('timerDisplay').innerText = `Time: ${timeRemaining}`;
    
    countdown = setInterval(() => {
        timeRemaining--;
        document.getElementById('timerDisplay').innerText = `Time: ${timeRemaining}`;
        if (timeRemaining <= 0) {
            clearInterval(countdown);
            calculateScore();
        }
    }, 1000);
    
    gameStarted = true;
}

function calculateScore() {
    if (!gameStarted) return;

    const red = parseInt(redSlider.value);
    const green = parseInt(greenSlider.value);
    const blue = parseInt(blueSlider.value);

    const maxDifference = 255 * 3;
    const actualDifference = Math.abs(red - targetColor.r) + Math.abs(green - targetColor.g) + Math.abs(blue - targetColor.b);
    let score = Math.max(0, Math.round((1 - actualDifference / maxDifference) * maxScore));

    if (gameMode === 'hard') {
        // In hard mode, penalize for remaining time
        const timeBonus = Math.round((timeRemaining / gameDuration) * 50);
        score = Math.max(0, score - timeBonus);
    }

    document.getElementById('scoreDisplay').innerText = `Your Score: ${score}`;
    clearInterval(countdown);
    gameStarted = false;

    document.getElementById('startButton').disabled = false;
    document.getElementById('restartButton').classList.remove('hidden');
}

function restartGame() {
    document.getElementById('sliders').classList.add('hidden');
    document.getElementById('startButton').disabled = false;
    document.getElementById('scoreDisplay').innerText = '';
    document.getElementById('timerDisplay').innerText = '';
    document.getElementById('colorDisplay').style.backgroundColor = '';
    redSlider.value = 0;
    greenSlider.value = 0;
    blueSlider.value = 0;
    updateColorPreview();
}

function updateColorPreview() {
    const red = redSlider.value;
    const green = greenSlider.value;
    const blue = blueSlider.value;
    colorPreview.style.backgroundColor = `rgb(${red}, ${green}, ${blue})`;
}

const gameModes = document.getElementById('gameModes');

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
    
    // Hide game mode buttons
    gameModes.classList.add('hidden');

    timeRemaining = gameDuration;
    document.getElementById('timerDisplay').innerText = `Time: ${timeRemaining}`;
    
    countdown = setInterval(() => {
        timeRemaining--;
        document.getElementById('timerDisplay').innerText = `Time: ${timeRemaining}`;
        if (timeRemaining <= 0) {
            clearInterval(countdown);
            calculateScore();
        }
    }, 1000);
    
    gameStarted = true;
}

function restartGame() {
    document.getElementById('sliders').classList.add('hidden');
    document.getElementById('startButton').disabled = false;
    document.getElementById('scoreDisplay').innerText = '';
    document.getElementById('timerDisplay').innerText = '';
    document.getElementById('colorDisplay').style.backgroundColor = '';
    redSlider.value = 0;
    greenSlider.value = 0;
    blueSlider.value = 0;
    updateColorPreview();
    
    // Show game mode buttons
    gameModes.classList.remove('hidden');
}


// Set initial game mode
setGameMode('normal');