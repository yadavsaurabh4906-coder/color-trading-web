function sendRegisterOTP() {
    let name = document.getElementById("regName").value.trim();
    let mobile = document.getElementById("regMobile").value.trim().replace(/\s+/g, '');
    let pwd = document.getElementById("regPwd").value.trim();
    let pin = document.getElementById("regPin").value.trim().replace(/\s+/g, '');
    let refCode = document.getElementById("regRef").value.trim().toUpperCase();

    if (mobile.startsWith("+91")) mobile = mobile.slice(3);
    if (mobile.startsWith("0")) mobile = mobile.slice(1);

    if (!name) return alert("Kripya apna Full Name bharein!");
    if (mobile.length !== 10 || isNaN(mobile)) return alert("Kripya 10-digit ka Mobile Number dalein!");
    if (!pwd) return alert("Kripya Password banayein!");
    if (pin.length !== 4 || isNaN(pin)) return alert("Kripya 4-digit Security PIN dalein!");

    db.ref(`users/${mobile}`).once("value", (snapshot) => {
        if (snapshot.exists()) return alert("Yeh Mobile Number pehle se registered hai!");

        // 4-digit OTP generate karein
        generatedOTP = Math.floor(1000 + Math.random() * 9000).toString();

        tempRegistrationData = {
            userId: "ROYAL" + mobile.slice(-4),
            name, mobile, pwd: hashText(pwd), pin: hashText(pin),
            balance: 0, refCode: "REF" + mobile.slice(-4),
            referredBy: refCode || null, refBonusClaimed: false
        };

        // Fast2SMS Direct API Call
        let apiKey = "4nGRVyholWbj97XxTMe6u5qiagIvY2w3tdUFC0HsEcQJm8NLPZhzjETCpNRakrVbGuWc5AKYIt9XLQBM";
        let url = `https://www.fast2sms.com/dev/bulkV2?authorization=${apiKey}&route=otp&variables_values=${generatedOTP}&flash=0&numbers=${mobile}`;

        fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.return) {
                alert(`📲 SMS Sent Successfully to +91 ${mobile}!`);
                document.getElementById("regFormSection").style.display = "none";
                document.getElementById("otpFormSection").style.display = "block";
            } else {
                alert("❌ SMS Error: " + (data.message[0] || "Fast2SMS Balance/Key Check karein"));
            }
        })
        .catch(err => {
            console.error(err);
            alert("❌ SMS Bhejne Me Network Error Aaya!");
        });
    });
}
