function sendFirebaseOTP() {
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
    btn.disabled = true;

    db.ref(`users/${mobile}`).once("value", (snapshot) => {
        btn.disabled = false;

        if (snapshot.exists()) {
            return alert("Yeh Mobile Number pehle se registered hai!");
        }

        // Generate 6-digit OTP locally for local testing
        generatedOTP = Math.floor(100000 + Math.random() * 900000).toString();

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

        alert(`📲 Local Verification OTP: ${generatedOTP}`);
        document.getElementById("regFormSection").style.display = "none";
        document.getElementById("otpFormSection").style.display = "block";
    });
}
