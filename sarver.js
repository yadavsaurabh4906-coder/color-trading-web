const admin = require("firebase-admin");

// Service Account Credentials from Firebase Admin Console
const serviceAccount = require("./serviceAccountKey.json");

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
  databaseURL: "https://royal-trading-9ea66-default-rtdb.firebaseio.com"
});

const db = admin.database();
const ROUND_DURATION = 30; // ⚡ Updated to 30 Seconds to match index.html
let processedRounds = {};

console.log("🚀 Server-Side Execution Engine Started...");

setInterval(async () => {
    const serverTime = Date.now();
    const currentSeconds = Math.floor(serverTime / 1000);
    const roundId = Math.floor(currentSeconds / ROUND_DURATION);
    const timer = ROUND_DURATION - (currentSeconds % ROUND_DURATION);

    if (timer === ROUND_DURATION && !processedRounds[roundId]) {
        processedRounds[roundId] = true;
        await evaluateServerRound(roundId);
    }
}, 500);

async function evaluateServerRound(roundId) {
    console.log(`⚡ Evaluating Round: ${roundId}`);
    const betsSnapshot = await db.ref("global_live_bets").once("value");
    const bets = betsSnapshot.val() || {};

    let globalLiveBets = { 
        color: { Red: 0, Green: 0, Violet: 0 }, 
        big_small: { Big: 0, Small: 0 }, 
        number: {} 
    };
    for(let i=0; i<10; i++) globalLiveBets.number[i] = 0;

    Object.values(bets).forEach(uBets => {
        if (uBets.color) {
            globalLiveBets.color.Red += (uBets.color.Red || 0);
            globalLiveBets.color.Green += (uBets.color.Green || 0);
            globalLiveBets.color.Violet += (uBets.color.Violet || 0);
        }
        if (uBets.big_small) {
            globalLiveBets.big_small.Big += (uBets.big_small.Big || 0);
            globalLiveBets.big_small.Small += (uBets.big_small.Small || 0);
        }
        if (uBets.number) {
            for(let i=0; i<10; i++) globalLiveBets.number[i] += (uBets.number[i] || 0);
        }
    });

    // Smart Anti-Loss Logic Execution
    let payouts = {};
    for (let n = 0; n < 10; n++) {
        let col = (n===1||n===3||n===7||n===9) ? "Green" : ((n===2||n===4||n===6||n===8) ? "Red" : "Violet");
        let size = n >= 5 ? "Big" : "Small";

        payouts[n] = (globalLiveBets.color[col] * 1.9) + 
                     (globalLiveBets.big_small[size] * 1.9) + 
                     (globalLiveBets.number[n] * 9.0);
    }
    let minPayout = Math.min(...Object.values(payouts));
    let bestNums = Object.keys(payouts).filter(k => payouts[k] === minPayout);
    let winningNum = parseInt(bestNums[Math.floor(Math.random() * bestNums.length)]);
    let winningColor = (winningNum===1||winningNum===3||winningNum===7||winningNum===9) ? "Green" : ((winningNum===2||winningNum===4||winningNum===6||winningNum===8) ? "Red" : "Violet");
    let winningSize = winningNum >= 5 ? "Big" : "Small";

    // Write Final Result to Firebase
    await db.ref(`global_results/${roundId}`).set({ number: winningNum, color: winningColor, timestamp: Date.now() });

    // Payout Winnings Securely
    for (let mobile in bets) {
        let uBet = bets[mobile];
        let win = false;
        let winnings = 0;

        if (uBet.number && uBet.number[winningNum]) {
            win = true; winnings += uBet.number[winningNum] * 9;
        }
        if (uBet.color && uBet.color[winningColor]) {
            win = true; winnings += Math.round(uBet.color[winningColor] * 1.9);
        }
        if (uBet.big_small && uBet.big_small[winningSize]) {
            win = true; winnings += Math.round(uBet.big_small[winningSize] * 1.9);
        }

        if (win && winnings > 0) {
            await db.ref(`users/${mobile}/balance`).transaction((bal) => (bal || 0) + winnings);
            console.log(`🎉 Paid ₹${winnings} to +91 ${mobile}`);
        }
    }

    // Reset Live Bets
    await db.ref("global_live_bets").remove();
}
