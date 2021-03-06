/*
 * This Java source file was generated by the Gradle 'init' task.
 */
package com.churchcommunitybuilder;

import com.google.api.client.http.GenericUrl;
import com.google.api.client.http.HttpRequestFactory;
import com.google.api.client.http.HttpResponse;
import com.google.api.client.http.HttpResponseException;
import com.google.api.client.http.json.JsonHttpContent;
import com.google.api.client.json.jackson2.JacksonFactory;
import com.google.common.base.Preconditions;
import org.json.JSONArray;

import java.io.IOException;

public class RestClient {

    private final HttpRequestFactory requestFactory;

    public RestClient(HttpRequestFactory httpRequestFactory) {
        this.requestFactory = Preconditions.checkNotNull(httpRequestFactory, "httpRequestFactory must not be null");
    }

    public JSONArray getJson(GenericUrl url) throws IOException {
        var request = this.requestFactory.buildGetRequest(url);
        Requests.addAcceptHeader(request);

        var response = request.execute();
        var jsonArray = parseJson(response);

        return jsonArray;
    }

    public JSONArray postJson(GenericUrl url, Object data) throws IOException {
        var content = createJsonHttpContent(data);
        var request = this.requestFactory.buildPostRequest(url, content);
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
