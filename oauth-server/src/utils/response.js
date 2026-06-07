export const apiResponse = (
  code,
  data,
  message,
  status = "success",
  service = "oauth-server",
) => ({
  status,
  code,
  data: data ?? null,
  message,
  timestamp: new Date().toISOString(),
  service,
});

module.exports = apiResponse;
