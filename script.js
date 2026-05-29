// script.js
document.addEventListener('DOMContentLoaded', () => {
    // Only run the game logic if we are on the play_quiz page
    const appDiv = document.getElementById('app');
    if (appDiv && typeof userRole !== 'undefined') {
        startGameLoop();
    }
});

let currentState = null;
let currentQuestionId = null;
let answered = false;

function startGameLoop() {
    setInterval(pollGameState, 1500); // Poll every 1.5 seconds
    pollGameState();
}

function pollGameState() {
    fetch('play_quiz.php?action=get_state')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                document.getElementById('app').innerHTML = `<h2 class="text-center mt-20">Error: ${data.error}</h2>`;
                return;
            }
            
            if (currentState !== data.status || currentQuestionId !== data.current_question_id) {
                currentState = data.status;
                currentQuestionId = data.current_question_id;
                answered = false; // reset when state/question changes
                renderState(data);
            } else if (currentState === 'waiting' && userRole === 'teacher') {
                // Update players list without full re-render
                const pList = document.getElementById('players-list');
                if (pList) {
                    pList.innerHTML = data.players.map(p => `<span>${p}</span>`).join(', ');
                }
            }
        });
}

function renderState(data) {
    const app = document.getElementById('app');
    
    if (data.status === 'waiting') {
        if (userRole === 'teacher') {
            app.innerHTML = `
                <div class="container text-center mt-20" style="color: white;">
                    <h1 style="font-size: 3rem; margin-bottom: 20px;">Join with PIN</h1>
                    <h2>Waiting for players...</h2>
                    <p style="font-size: 1.5rem; margin-top: 20px;">Players joined: <span id="players-list" style="font-weight:bold;">${data.players.join(', ')}</span></p>
                    <button class="btn btn-green mt-20" style="font-size: 1.5rem;" onclick="teacherStart()">Start Game</button>
                </div>
            `;
        } else {
            app.innerHTML = `
                <div class="container text-center mt-20" style="color: white;">
                    <h1>You're in!</h1>
                    <p style="font-size: 1.5rem; margin-top: 20px;">See your nickname on screen.<br>Waiting for teacher to start...</p>
                </div>
            `;
        }
    } else if (data.status === 'playing') {
        const q = data.question;
        const ans = data.answers;
        const colors = ['ans-red', 'ans-blue', 'ans-yellow', 'ans-green'];
        
        if (userRole === 'teacher') {
            app.innerHTML = `
                <div class="play-header">
                    <span>Question</span>
                </div>
                <div class="question-container">
                    ${q.question_text}
                </div>
                <div class="answers-play-grid">
                    ${ans.map((a, i) => `<div class="answer-btn ${colors[i]}" style="cursor:default;">${a.answer_text}</div>`).join('')}
                </div>
                <div class="text-center mt-20" style="margin-bottom: 40px;">
                    <button class="btn btn-dark" style="font-size:1.5rem;" onclick="teacherNext()">Next Question / End Game</button>
                </div>
            `;
        } else {
            if (!answered) {
                app.innerHTML = `
                    <div class="play-header text-center" style="display:block;">
                        <span style="font-size: 2rem;">${q.question_text}</span>
                    </div>
                    <div class="answers-play-grid mt-20">
                        ${ans.map((a, i) => `<button class="answer-btn ${colors[i]}" onclick="submitAnswer(${a.id}, ${q.id})">${a.answer_text}</button>`).join('')}
                    </div>
                `;
            }
        }
    } else if (data.status === 'finished') {
        app.innerHTML = `
            <div class="container text-center mt-20" style="color: white;">
                <h1>Game Finished!</h1>
                ${userRole === 'teacher' ? '<a href="dashboard.php" class="btn btn-outline mt-20">Back to Dashboard</a>' : ''}
            </div>
            <div id="leaderboard-container"></div>
        `;
        showLeaderboard();
    }
}

function teacherStart() {
    fetch('play_quiz.php?action=teacher_start').then(() => pollGameState());
}

function teacherNext() {
    fetch('play_quiz.php?action=teacher_next').then(() => pollGameState());
}

function submitAnswer(ansId, qId) {
    answered = true;
    document.getElementById('app').innerHTML = `
        <div class="container text-center mt-20" style="color: white;">
            <h1>Answer Submitted!</h1>
            <p style="font-size: 1.5rem;">Waiting for teacher to proceed...</p>
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
        let html = '<div class="leaderboard"><h2 style="color:#333;">Leaderboard</h2>';
        data.forEach((p, i) => {
            let cls = i===0 ? 'lb-1' : (i===1 ? 'lb-2' : (i===2 ? 'lb-3' : ''));
            html += `<div class="lb-row ${cls}"><span>${i+1}. ${p.nickname}</span><span>${p.score} pts</span></div>`;
        });
        if(data.length === 0) {
            html += '<p style="color:#333;text-align:center;">No scores yet.</p>';
        }
        html += '</div>';
        document.getElementById('leaderboard-container').innerHTML = html;
    });
}
