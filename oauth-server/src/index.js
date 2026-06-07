const express = require("express");
const router = require("./routes/oauthRoute");
const errorHandler = require("./middleware/errorHandler");
const app = express();
const port = 3002;
app.get("/", (req, res) => {
  return res.status(200).json({ success: true, message: "hello folks!!!" });
});
app.use("/auth", router);
app.use(errorHandler);
app.listen(port, () => {
  console.log(`Example app listening on port ${port}`);
});
