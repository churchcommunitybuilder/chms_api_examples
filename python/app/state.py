"""This module contains methods for working with a hash as a kind of simple in-memory database,
strictly for the purpose an example. You would not want to use this in a real scenario.

The data will be reset every time the server is restart, and the data is shared across threads,
meaning multiple users can't have different state.
"""
import os

STATE = {
    "client_id": os.environ["CCB_CLIENT_ID"],
    "client_key": os.environ["CCB_CLIENT_KEY"],
    "access_token": None,
    "refresh_token": None,
    "token_expires_at": 0,
}


def get_state(key, fallback=None):
    return STATE.get(key, fallback)


def set_state(key, value):
    STATE[key] = value
