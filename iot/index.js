const express = require("express");
const mqtt = require("mqtt");
const app = express();
const path = require("path");
const sound = require("sound-play");
const dotenv = require("dotenv");
dotenv.config();
app.use(require("cors")());
app.use(express.json());

const deviceData = {};

let connectUrl, configMqtt;

// Dev: broker publik, no TLS, no auth
const connectUrl = "mqtt://broker.hivemq.com:1883";
const configMqtt = {
  clientId: "server-dev-" + Math.random().toString(16).substr(2, 8),
  connectTimeout: 4000,
  reconnectPeriod: 1000,
};

const mqttClient = mqtt.connect(connectUrl, configMqtt);

mqttClient.on("connect", () => {
  mqttClient.subscribe("iot/noise/#");
  console.log("MQTT Bridge aktif, subscribed: iot/noise/#");
});

mqttClient.on("message", (topic, message) => {
  try {
    const data = JSON.parse(message.toString());
    if (data.kebisingan > 50) {
      const audio = path.join(__dirname, "..", "audio", "alert.wav");
      sound.play(audio);
    }
    deviceData[data.device_id] = {
      ...data,
      receivedAt: new Date().toISOString(),
    };
    console.log(`[${data.device_id}]`, data);
  } catch (e) {
    console.error("Parse error:", e);
  }
});

mqttClient.on("error", (err) => {
  console.error("MQTT error:", err.message);
});

// REST Endpoints
app.get("/api/devices", (req, res) => {
  res.json({
    devices: Object.keys(deviceData),
    count: Object.keys(deviceData).length,
  });
});

app.get("/api/devices/:id/data", (req, res) => {
  const data = deviceData[req.params.id];
  if (!data) return res.status(404).json({ error: "Device not found" });
  res.json(data);
});

app.post("/api/devices/:id/command", (req, res) => {
  const { command, value } = req.body;
  const topic = `iot/commands/${req.params.id}`;
  mqttClient.publish(topic, JSON.stringify({ command, value }));
  res.json({ status: "sent", topic });
});

app.listen(3000, () =>
  console.log(`IoT Service running on port 3000 [${IS_DEV ? "DEV" : "PROD"}]`),
);
