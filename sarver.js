const express = require('express');
const { createClient } = require('@supabase/supabase-js');
const path = require('path');
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static(__dirname));

const SUPABASE_URL = "https://gbyrdjwcbfsdibnrvqzz.supabase.co";
const SUPABASE_KEY = "sb_publishable_KyF98L8mcaHentW4gqhB1Q_2bAlCMaF"; 
const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);

// 🧠 Anti-Loss Calculation (Number = 9x | Color = 1.9x)
app.get('/api/calculate-result', async (req, res) => {
    try {
        const { data: bets, error } = await supabase.from('live_bets').select('*');
        if (error) throw error;

        let colorBets = { Red: 0, Green: 0, Violet: 0 };
        let numberBets = { 0:0, 1:0, 2:0, 3:0, 4:0, 5:0, 6:0, 7:0, 8:0, 9:0 };

        (bets || []).forEach(b => {
            if (b.choice_type === 'color') colorBets[b.choice] = (colorBets[b.choice] || 0) + Number(b.amount);
            if (b.choice_type === 'number') numberBets[b.choice] = (numberBets[b.choice] || 0) + Number(b.amount);
        });

        let payouts = {};
        for (let n = 0; n <= 9; n++) {
            let color = (n === 0 || n === 5) ? 'Violet' : ([1, 3, 7, 9].includes(n) ? 'Green' : 'Red');
            let colorPayout = (colorBets[color] || 0) * 1.9;
            let numberPayout = (numberBets[n] || 0) * 9.0;
            payouts[n] = colorPayout + numberPayout;
        }

        let minPayout = Math.min(...Object.values(payouts));
        let bestNumbers = Object.keys(payouts).filter(n => payouts[n] === minPayout);
        let winningNumber = parseInt(bestNumbers[Math.floor(Math.random() * bestNumbers.length)]);

        // Clear bets for next round
        await supabase.from('live_bets').delete().neq('id', 0);

        res.json({ success: true, winningNumber });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
});

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(Server running on port ${PORT}));

