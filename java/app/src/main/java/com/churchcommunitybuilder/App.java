/*
 * This Java source file was generated by the Gradle 'init' task.
 */
package com.churchcommunitybuilder;

import com.google.api.client.auth.oauth2.Credential;
import com.google.api.client.http.GenericUrl;
import com.google.api.client.http.HttpResponse;
import com.google.api.client.http.HttpResponseException;
import com.google.api.client.http.HttpTransport;
import com.google.api.client.http.json.JsonHttpContent;
import com.google.api.client.json.jackson2.JacksonFactory;
import com.google.common.base.Preconditions;
import org.json.JSONArray;

import java.io.IOException;

public class App {

    private final HttpTransport transport;
    private final Credential credentials;

    public App(HttpTransport transport, Credential credentials) {
        this.transport = Preconditions.checkNotNull(transport, "transport must not be null");
        this.credentials = Preconditions.checkNotNull(credentials, "credentials must not be null");
    }

    /**
     * @param url
     * @return
     * @throws HttpResponseException
     * @throws IOException
     */
    public JSONArray getJson(GenericUrl url) throws IOException {
        var requestFactory = transport.createRequestFactory(credentials);
        var request = requestFactory.buildGetRequest(url);
        Requests.addAcceptHeader(request);

        var response = request.execute();
        var jsonArray = parseJson(response);

        return jsonArray;
    }

    /**
     * @param url
     * @param data
     * @return
     * @throws HttpResponseException
     * @throws IOException
     */
    public JSONArray postJson(GenericUrl url, Object data) throws IOException {
        var requestFactory = transport.createRequestFactory(credentials);
        var content = createJsonHttpContent(data);
        var request = requestFactory.buildPostRequest(url, content);
        Requests.addAcceptHeader(request);

        var response = request.execute();
        var jsonArray = parseJson(response);

        return jsonArray;
    }

    private static JsonHttpContent createJsonHttpContent(Object data) {
        var factory = JacksonFactory.getDefaultInstance();

        return new JsonHttpContent(factory, data);
    }

    private static JSONArray parseJson(HttpResponse response) throws IOException {
        var exception = new HttpResponseException(response);
        var contentType = response.getContentType();
        if (!exception.isSuccessStatusCode() || !contentType.startsWith("application/json")) {
            throw exception;
        }

        return new JSONArray(exception.getContent());
    }

}
