/**
 * iot/node-red-data/settings.js
 * Konfigurasi Node-RED untuk Smart City Bridge
 */

module.exports = {
  // Port Node-RED
  uiPort: process.env.PORT || 1880,

  // Direktori flows
  userDir: "/data",
  flowFile: "flows.json",

  // Context storage — pakai memory + file agar queue persisten
  contextStorage: {
    default: { module: "memory" },
    persistent: { module: "localfilesystem" },
  },

  // Environment variables yang tersedia di function nodes
  // Akses via: env.get('GATEWAY_URL')
  functionGlobalContext: {
    GATEWAY_URL: process.env.GATEWAY_URL || "http://api-gateway:3000",
    IOT_TOKEN: process.env.IOT_TOKEN || "",
    MQTT_BROKER: process.env.MQTT_BROKER || "mosquitto",
    MQTT_PORT: process.env.MQTT_PORT || "1883",
  },

  // Logging
  logging: {
    console: {
      level: "info",
      metric: false,
      audit: false,
    },
  },

  // Editor UI
  adminAuth: null, // Nonaktifkan auth untuk development

  // Matikan telemetry
  editorTheme: {
    tours: false,
  },

  // Ekspos env vars ke function nodes
  exportGlobalContextKeys: false,
};
