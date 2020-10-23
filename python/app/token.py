"""This module contains methods for working with the Church Community Builder oauth token system.
"""
import time
from urllib.parse import urlencode

from app.constants import API_BASE_URL, APP_BASE_URL, OAUTH_BASE_URL
from app.request import post_json
from app.state import get_state, set_state


def get_authorization_url():
    """Builds the CCB oauth authorization URL, using your client ID."""
    url_base = f"{OAUTH_BASE_URL}/oauth/authorize"
    query = urlencode(
        {
            "client_id": get_state("client_id"),
            "response_type": "code",
            "redirect_uri": APP_BASE_URL + "/auth",
        }
    )
    return f"{url_base}?{query}"


def get_access_token(code):
    """Requests an access token / refresh token pair from CCB using the authorization code
    obtained from the authorization process.
    """
    url = f"{API_BASE_URL}/oauth/token"
    data = {
        "grant_type": "authorization_code",
        "code": code,
        "client_id": get_state("client_id"),
        "client_secret": get_state("client_key"),
        "redirect_uri": APP_BASE_URL + "/auth",
    }
    response = post_json(url, data)
    set_access_token(response.json())


def set_access_token(data):
    """Store the token data that we need to make requests."""
    set_state("access_token", data["access_token"])
    set_state("refresh_token", data["refresh_token"])
    set_state("token_expires_at", time.time() + data["expires_in"])
