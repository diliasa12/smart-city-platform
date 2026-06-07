const express = require("express");
const OAuth2Server = require("oauth2-server");

const oauth = new OAuth2Server({
  model: require("./models/model"),
});
const app = express();
const port = 3002;

app.get("/auth", (req, res) => {
  console.log(req.url);
  res.send("Hello World!");
});

app.listen(port, () => {
  console.log(`Example app listening on port ${port}`);
});
