function apiResponse(
  code,
  data,
  message,
  status = "success",
  service = "api-gateway",
) {
  return {
    status,
    code,
    data: data ?? null,
    message,
    timestamp: new Date().toISOString(),
    service,
  };
}

module.exports = { apiResponse };
