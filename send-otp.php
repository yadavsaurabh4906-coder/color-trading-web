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

    let btn = document.getElementById("sendOtpBtn");
    if(btn) {
        btn.disabled = true;
        btn.innerText = "⏳ Bhej Raha Hai...";
    }

    db.ref(`users/${mobile}`).once("value", (snapshot) => {
        if (snapshot.exists()) {
            if(btn) {
                btn.disabled = false;
                btn.innerText = "📩 Send OTP via SMS";
            }
            return alert("Yeh Mobile Number pehle se registered hai!");
        }

        generatedOTP = Math.floor(1000 + Math.random() * 9000).toString();

        tempRegistrationData = {
            userId: "ROYAL" + mobile.slice(-4),
            name, mobile, 
            pwd: hashText(pwd), 
            pin: hashText(pin),
            balance: 0, 
            refCode: "REF" + mobile.slice(-4),
            referredBy: refCode || null, 
            refBonusClaimed: false
        };

        // Direct Local Vercel Serverless Route
        fetch("/api/send-otp", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ mobile: mobile, otp: generatedOTP })
        })
        .then(res => res.json())
        .then(data => {
            if(btn) {
                btn.disabled = false;
                btn.innerText = "📩 Send OTP via SMS";
            }
            if (data.return) {
                alert(`📲 OTP successfully sent to +91 ${mobile}!`);
                document.getElementById("regFormSection").style.display = "none";
                document.getElementById("otpFormSection").style.display = "block";
            } else {
                alert("❌ Fast2SMS Message: " + (data.message ? data.message[0] : "SMS Send Nahi Hua"));
            }
        })
        .catch(err => {
            if(btn) {
                btn.disabled = false;
                btn.innerText = "📩 Send OTP via SMS";
            }
            console.error(err);
            alert("❌ Server Connection Error!");
        });
    });
}
