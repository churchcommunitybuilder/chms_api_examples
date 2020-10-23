package com.churchcommunitybuilder;

import com.google.api.client.http.HttpRequest;

public class Accept {

    public static final String ACCEPT_KEY = "Accept";
    public static final String ACCEPT_VALUE = "application/vnd.ccbchurch.v2+json";

    private Accept() {
    }

    public static void accept(HttpRequest request) {
        var headers = request.getHeaders();
        headers.put(ACCEPT_KEY, ACCEPT_VALUE);
    }

}
