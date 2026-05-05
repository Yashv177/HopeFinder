document.addEventListener("DOMContentLoaded", () => {

  const loginForm = document.querySelector(".login-form");
  const signupForm = document.querySelector(".signup-form");
  const goSignup = document.getElementById("goSignup");
  const goLogin = document.getElementById("goLogin");

  // toggle login → signup
  goSignup.addEventListener("click", () => {
    loginForm.classList.remove("active");
    signupForm.classList.add("active");
  });

  // toggle signup → login
  goLogin.addEventListener("click", () => {
    signupForm.classList.remove("active");
    loginForm.classList.add("active");
  });

  // Generate CAPTCHA
  window.generateCaptcha = function () {
    const text = Math.random().toString(36).substring(2, 8).toUpperCase();
    document.getElementById("captchaText").innerText = text;
    window.currentCaptcha = text;
  };

  window.generateCaptcha2 = function () {
    const t = Math.random().toString(36).substring(2, 8).toUpperCase();
    document.getElementById("captchaText2").innerText = t;
    window.currentCaptcha2 = t;
  };

  // Validate login captcha
  window.validateCaptcha = function () {
    return document.getElementById("captchaInput").value === window.currentCaptcha;
  };

  window.validateSignupCaptcha = function () {
    return document.getElementById("captchaInput2").value === window.currentCaptcha2;
  };

  generateCaptcha();
  generateCaptcha2();
});
