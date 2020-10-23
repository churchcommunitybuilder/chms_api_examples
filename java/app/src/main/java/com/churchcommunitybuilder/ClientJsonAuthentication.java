package com.churchcommunitybuilder;

import com.google.api.client.auth.oauth2.ClientParametersAuthentication;
import com.google.api.client.http.HttpRequest;
import com.google.api.client.http.UrlEncodedContent;
import com.google.api.client.http.json.JsonHttpContent;
import com.google.api.client.json.JsonFactory;
import com.google.api.client.util.Data;
import com.google.common.base.Preconditions;

import java.io.IOException;
import java.util.Map;

public class ClientJsonAuthentication extends ClientParametersAuthentication {

    private final JsonFactory jsonFactory;
    private final String subdomain;

    public ClientJsonAuthentication(JsonFactory jsonFactory, String clientId, String clientSecret, String subdomain) {
        super(clientId, clientSecret);

        this.jsonFactory = Preconditions.checkNotNull(jsonFactory, "jsonFactory must not be null"); // TODO cover with unit test
        this.subdomain = Preconditions.checkNotNull(subdomain, "subdomain must not be null"); // TODO cover with unit test
    }

    @Override
    public void intercept(HttpRequest request) throws IOException {
        super.intercept(request);

        Accept.accept(request);

        var content = UrlEncodedContent.getContent(request);
        Map<String, Object> data = Data.mapOf(content.getData());
        data.put("grant_type", "authorization_code");
        data.put("subdomain", subdomain);

        var jsonContent = new JsonHttpContent(this.jsonFactory, data);
        request.setContent(jsonContent);
    }

    /**
     * @return the subdomain
     */
    public String getSubdomain() {
        return subdomain;
    }

}
