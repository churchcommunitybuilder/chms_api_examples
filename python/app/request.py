"""This module contains wrappers around the requests library for making requests to the CCB
API.
"""
import requests

from app.state import get_state


def post_json(url, data, *, auth=False):
    """This method is a simple wrapper around requests.post that will add the appropriate headers
    needed for working with the CCB API.
    """
    # Requests without the Accept: application/vnd.ccbchurch.vd+json header will be rejected
    headers = {
        "Content-Type": "application/json",
        "Accept": "application/vnd.ccbchurch.v2+json",
    }
    if auth:
        access_token = get_state("access_token", "")
        if access_token:
            headers["Authorization"] = f"Bearer {access_token}"

    return requests.post(url, json=data, headers=headers)


def get_json(url, params=None, *, auth=False):
    """This method is a simple wrapper around requests.get that will add the appropriate headers
    needed for working with the CCB API.
    """
    # Requests without the Accept: application/vnd.ccbchurch.vd+json header will be rejected
    headers = {
        "Accept": "application/vnd.ccbchurch.v2+json",
    }
    if auth:
        access_token = get_state("access_token")
        if access_token:
            headers["Authorization"] = f"Bearer {access_token}"
    return requests.get(url, params, headers=headers)
