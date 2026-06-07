const OAuth2Server = require("@node-oauth/oauth2-server");
const OAuthModel = require("../models/model");
const oauth = new OAuth2Server({
  model: OAuthModel,
  accessTokenLifetime: parseInt(process.env.ACCESS_TOKEN_TTL || "3600"),
  refreshTokenLifetime: parseInt(process.env.REFRESH_TOKEN_TTL || "604800"),
  allowBearerTokensInQueryString: false,
  allowEmptyState: false,
  requireClientAuthentication: {
    password: false, // citizen login tidak wajib client_secret
    client_credentials: true, // service-to-service wajib client_secret
    refresh_token: false,
  },
});

module.exports = oauth;
