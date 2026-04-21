function togglePasswordVisibility() {
  let x = document.getElementById("passwordInput");
  let x2 = document.getElementById("eye");
  if (x.type === "password") {
  x.type = "text";
  x2.src = "./media/images/privacyViewing.png";
  x2.alt = "Hide Password";
} else {
  x.type = "password";
  x2.src = "./media/images/privacyHiding.png";
  x2.alt = "Show Password";
}
}