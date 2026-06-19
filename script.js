function startCountdown(){
  let timer = 60;
  function countdown(){
    if(timer > 0){
      document.getElementById("timer").innerHTML = "⏳ " + timer + "s remaining";
      timer--;
      setTimeout(countdown,1000);
    } else {
      document.getElementById("otp").innerHTML = "❌ Expired!";
    }
  }
  countdown();
}

function revealOTP(){
  document.getElementById("otp").classList.remove("hidden");
}

function toggleTheme(){
  document.body.classList.toggle("light");
  document.body.classList.toggle("dark");
  let mode = document.body.classList.contains("light") ? "🌞 Light Mode" : "🌙 Dark Mode";
  document.getElementById("themeStatus").innerText = mode;
}