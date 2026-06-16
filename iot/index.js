const express = require("express");
const mqtt = require("mqtt");
const app = express();
app.use(require("cors")());
app.use(express.json());

// ─── Toggle ini saat deploy ───────────────────────────
const IS_DEV = process.env.NODE_ENV !== "production";
// atau set manual: const IS_DEV = true;
// ──────────────────────────────────────────────────────

const deviceData = {};

let connectUrl, configMqtt;

if (IS_DEV) {
  // Dev: broker publik, no TLS, no auth
  connectUrl = "mqtt://broker.hivemq.com:1883";
  configMqtt = {
    clientId: "server-dev-" + Math.random().toString(16).substr(2, 8),
    connectTimeout: 4000,
    reconnectPeriod: 1000,
  };
  console.log("[MODE] Development - broker publik");
} else {
  // Produksi: cluster sendiri
  connectUrl =
    "mqtts://b15eff722b7a429d8c77f46c34e468d1.s1.eu.hivemq.cloud:8883";
  configMqtt = {
    clientId: "server-prod-" + Math.random().toString(16).substr(2, 8),
    connectTimeout: 4000,
    username: "Kelompok3",
    password: "Kelompok3",
    reconnectPeriod: 1000,
  };
  console.log("[MODE] Production - HiveMQ Cloud");
}

const mqttClient = mqtt.connect(connectUrl, configMqtt);

mqttClient.on("connect", () => {
  mqttClient.subscribe("iot/noise/#");
  console.log("MQTT Bridge aktif, subscribed: iot/noise/#");
});

mqttClient.on("message", (topic, message) => {
  try {
    const data = JSON.parse(message.toString());
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
