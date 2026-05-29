// script.js - Updated for Creative Design
document.addEventListener('DOMContentLoaded', () => {
    const appDiv = document.getElementById('app');
    if (appDiv && typeof userRole !== 'undefined') {
        startGameLoop();
    }
});

let currentState = null;
let currentQuestionId = null;
let answered = false;
let timerInterval = null;
let timeLeft = 0;

function startGameLoop() {
    setInterval(pollGameState, 1500);
    pollGameState();
}

function pollGameState() {
    fetch('play_quiz.php?action=get_state')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                document.getElementById('app').innerHTML = `<div class="hero-section"><h2>Error: ${data.error}</h2></div>`;
                return;
            }
            
            if (currentState !== data.status || currentQuestionId !== data.current_question_id) {
                currentState = data.status;
                currentQuestionId = data.current_question_id;
                answered = false;
                if (timerInterval) clearInterval(timerInterval);
                renderState(data);
            } else if (currentState === 'waiting' && userRole === 'teacher') {
                const pList = document.getElementById('players-list');
                if (pList) {
                    pList.innerHTML = data.players.map(p => `<span class="glass-card" style="padding: 10px 20px; border-radius: 12px;">${p}</span>`).join(' ');
                }
            }
        });
}

function startTimer(duration) {
    if (timerInterval) clearInterval(timerInterval);
    timeLeft = duration;
    
    timerInterval = setInterval(() => {
        timeLeft--;
        const timerEl = document.getElementById('timer-count');
        if (timerEl) timerEl.innerText = timeLeft;
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            if (!answered && userRole === 'student' && currentState === 'playing') {
                handleTimeout();
            }
        }
    }, 1000);
}

function handleTimeout() {
    answered = true;
    document.getElementById('app').innerHTML = `
        <div class="hero-section">
            <div class="hero-logo" style="font-size: 3rem; color: var(--danger);">Time's Up!</div>
            <div class="glass-card" style="padding: 40px; text-align: center;">
                <p style="font-size: 1.5rem;">You weren't fast enough! Waiting for the next challenge...</p>
            </div>
        </div>
    `;
}

function renderState(data) {
    const app = document.getElementById('app');
    
    if (data.status === 'waiting') {
        if (userRole === 'teacher') {
            app.innerHTML = `
                <div class="hero-section">
                    <h1 class="hero-logo" style="font-size: 4rem;">Waiting for Players</h1>
                    <p style="font-size: 1.5rem; color: var(--accent); margin-bottom: 40px;">Join at localhost/quiz_app with PIN: <strong style="font-size: 3rem; color: white; display: block; margin-top: 10px;">${data.game_pin}</strong></p>
                    <div id="players-list" style="display: flex; gap: 15px; flex-wrap: wrap; justify-content: center; max-width: 800px; margin-bottom: 40px;">
                        ${data.players.map(p => `<span class="glass-card" style="padding: 10px 20px; border-radius: 12px;">${p}</span>`).join(' ')}
                    </div>
                    <button class="btn btn-primary" style="font-size: 1.5rem; padding: 20px 50px;" onclick="teacherStart()">Start Game</button>
                </div>
            `;
        } else {
            app.innerHTML = `
                <div class="hero-section">
                    <h1 class="hero-logo">You're In!</h1>
                    <div class="glass-card" style="padding: 40px; text-align: center;">
                        <p style="font-size: 1.5rem;">Look for your nickname on the screen.</p>
                        <p style="color: var(--accent); margin-top: 10px;">Wait for the teacher to start the fun!</p>
                    </div>
                </div>
            `;
        }
    } else if (data.status === 'playing') {
        const q = data.question;
        const ans = data.answers;
        startTimer(q.time_limit);
        
        if (userRole === 'teacher') {
            app.innerHTML = `
                <div class="container">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div class="glass-card" style="padding: 10px 25px; font-size: 1.5rem; font-weight: 800; color: var(--warning);">
                            TIME: <span id="timer-count">${q.time_limit}</span>s
                        </div>
                    </div>
                    <div class="glass-card question-title" style="margin-top: 0;">
                        ${q.question_text}
                    </div>
                    <div class="play-grid">
                        ${ans.map((a, i) => `<div class="ans-card ans-${i}" style="cursor:default;">${a.answer_text}</div>`).join('')}
                    </div>
                    <div style="margin-top: 50px; text-align: center;">
                        <button class="btn btn-outline" style="font-size: 1.2rem;" onclick="teacherNext()">Next Challenge →</button>
                    </div>
                </div>
            `;
        } else {
            if (!answered) {
                app.innerHTML = `
                    <div class="container">
                        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                            <div class="glass-card" style="padding: 10px 25px; font-size: 1.5rem; font-weight: 800; color: var(--warning);">
                                <span id="timer-count">${q.time_limit}</span>s LEFT
                            </div>
                        </div>
                        <div class="glass-card" style="padding: 30px; margin-bottom: 40px; font-size: 2rem; font-weight: 800; text-align: center;">
                            ${q.question_text}
                        </div>
                        <div class="play-grid">
                            ${ans.map((a, i) => `<button class="ans-card ans-${i}" onclick="submitAnswer(${a.id}, ${q.id})">${a.answer_text}</button>`).join('')}
                        </div>
                    </div>
                `;
            }
        }
    } else if (data.status === 'finished') {
        if (timerInterval) clearInterval(timerInterval);
        app.innerHTML = `
            <div class="hero-section">
                <h1 class="hero-logo" style="font-size: 4rem;">Game Over!</h1>
                <div id="leaderboard-container" style="width: 100%;"></div>
                <div style="margin-top: 40px;">
                    ${userRole === 'teacher' ? '<a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>' : '<a href="index.php" class="btn btn-outline">Play Again</a>'}
                </div>
            </div>
        `;
        showLeaderboard();
    }
}

function teacherStart() { fetch('play_quiz.php?action=teacher_start'); }
function teacherNext() { fetch('play_quiz.php?action=teacher_next'); }

function submitAnswer(ansId, qId) {
    answered = true;
    if (timerInterval) clearInterval(timerInterval);
    document.getElementById('app').innerHTML = `
        <div class="hero-section">
            <div class="hero-logo" style="font-size: 3rem;">Done!</div>
            <div class="glass-card" style="padding: 40px;">
                <p style="font-size: 1.5rem;">Answer submitted. Waiting for results...</p>
            </div>
        </div>
    `;
    fetch('play_quiz.php?action=submit_answer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ answer_id: ansId, question_id: qId })
    });
}

function showLeaderboard() {
    fetch('leaderboard.php?session_id=' + sessionId)
    .then(res => res.json())
    .then(data => {
        let html = '<div class="leaderboard-list">';
        data.forEach((p, i) => {
            let cls = i===0 ? 'lb-gold' : (i===1 ? 'lb-silver' : (i===2 ? 'lb-bronze' : ''));
            html += `<div class="lb-item ${cls}"><span>${i+1}. ${p.nickname}</span><span>${p.score} PTS</span></div>`;
        });
        html += '</div>';
        document.getElementById('leaderboard-container').innerHTML = html;
    });
}
