package com.churchcommunitybuilder;

import com.google.api.client.auth.oauth2.ClientParametersAuthentication;
import com.google.api.client.http.HttpRequest;
import com.google.api.client.http.UrlEncodedContent;
import com.google.api.client.http.json.JsonHttpContent;
import com.google.api.client.json.jackson2.JacksonFactory;
import com.google.api.client.util.Data;

import java.io.IOException;
import java.util.Map;

public class ClientJsonAuthentication extends ClientParametersAuthentication {

    public ClientJsonAuthentication(String clientId, String clientSecret) {
        super(clientId, clientSecret);
    }

    @Override
    public void intercept(HttpRequest request) throws IOException {
        super.intercept(request);

        Requests.addAcceptHeader(request);

        var jsonFactory = JacksonFactory.getDefaultInstance();
        var content = UrlEncodedContent.getContent(request);
        Map<String, Object> data = Data.mapOf(content.getData());
        var jsonContent = new JsonHttpContent(jsonFactory, data);
        request.setContent(jsonContent);
    }

}
