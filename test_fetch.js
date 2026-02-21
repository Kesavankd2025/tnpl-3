const payload = {
  name: "Node Test",
  email: "test@example.com",
  phone: "1234567890",
  message: "Testing from node"
};

fetch("http://localhost/Aruna/send_mail.php", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(payload)
})
.then(res => res.text())
.then(text => console.log("PHP RESPONSE: " + text))
.catch(err => console.error(err));
