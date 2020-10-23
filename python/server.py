"""This module exposes a Flask app that will connect to the CCB oauth API and display a paged
church directory.
"""
from flask import Flask, redirect, render_template, request

from app.constants import API_BASE_URL
from app.request import get_json
from app.state import get_state
from app.token import get_access_token, get_authorization_url

app = Flask(__name__)


@app.route("/")
def index():
    """This endpoint displays the church directory if the application has been connected to CCB,
    otherwise it redirects the user to a page to initiate the integration.
    """
    if not get_state("access_token"):
        return redirect("/integrations")

    page = request.args.get("page", 1)

    response = get_json(f"{API_BASE_URL}/individuals", {"page": page, "per_page": 100})

    people = response.json()
    record_count = int(response.headers.get("X-Total", 0))
    current_page = int(response.headers.get("X-Page", 1))
    next_page = int(response.headers.get("X-Next-Page", 0))
    last_page = int(response.headers.get("X-Total-Pages", 0))

    return render_template(
        "index.html",
        people=people,
        record_count=record_count,
        current_page=current_page,
        next_page=next_page,
        last_page=last_page,
    )


@app.route("/integrations", methods=["GET"])
def render_request_integration_form():
    """Displays a form allowing the user to initiate the integration process."""
    return render_template("request-integration.html")


@app.route("/integrations", methods=["POST"])
def initiate_integration():
    """Post back endpoint for the integration initiation form."""
    url = get_authorization_url()
    return redirect(url)


@app.route("/auth", methods=["GET"])
def integration_authorized():
    """Once the user has initiated the integration, CCB will redirect back to this endpoint with
    the authorization code as a query parameter. That code is then exchanged for an access token /
    refresh token pair.
    """
    code = request.args.get("code")
    get_access_token(code)
    return redirect("/")
