// Секундомер на чистом JS, без зависимостей.
//
// Главный принцип: время мы считаем НЕ инкрементом счётчика, а от «меток»
// performance.now(). Из-за этого таймер не отстаёт даже если браузер
// на мгновение «залип» — мы каждый раз пересчитываем фактическое время.

const minutesEl = document.getElementById('minutes');
const secondsEl = document.getElementById('seconds');
const centisEl = document.getElementById('centis');

const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resetBtn = document.getElementById('resetBtn');

let running = false;   // идёт ли сейчас отсчёт
let startTime = 0;     // метка момента нажатия «Старт»
let elapsed = 0;       // уже накопленные миллисекунды ( между паузами )
let timerId = null;    // id интервала, чтобы потом можно было остановить

// Дополняет число нулями до двух цифр: 5 → "05"
const pad = (n) => String(n).padStart(2, '0');

// Пересчитывает и выводит время на экран
function render() {
    // Суммарное время = накопленное ранее + то, что идёт прямо сейчас
    const totalMs = elapsed + (running ? performance.now() - startTime : 0);

    const minutes = Math.floor(totalMs / 60000);
    const seconds = Math.floor((totalMs % 60000) / 1000);
    const centis = Math.floor((totalMs % 1000) / 10); // сотые доли секунды

    minutesEl.textContent = pad(minutes);
    secondsEl.textContent = pad(seconds);
    centisEl.textContent = pad(centis);
}

function start() {
    if (running) return; // защита от двойного клика
    running = true;
    startTime = performance.now();
    timerId = setInterval(render, 10); // обновляем экран каждые ~10 мс

    startBtn.disabled = true;
    pauseBtn.disabled = false;
    resetBtn.disabled = false;
}

function pause() {
    if (!running) return;
    running = false;
    // Запоминаем, сколько успело пройти, и останавливаем интервал
    elapsed += performance.now() - startTime;
    clearInterval(timerId);

    startBtn.disabled = false;
    pauseBtn.disabled = true;
}

function reset() {
    running = false;
    clearInterval(timerId);
    elapsed = 0;
    render(); // сразу показываем 00:00.00

    startBtn.disabled = false;
    pauseBtn.disabled = true;
    resetBtn.disabled = true;
}

startBtn.addEventListener('click', start);
pauseBtn.addEventListener('click', pause);
resetBtn.addEventListener('click', reset);

render(); // вывести начальное значение на экран
