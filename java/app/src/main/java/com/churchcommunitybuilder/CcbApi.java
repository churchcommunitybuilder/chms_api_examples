package com.churchcommunitybuilder;

import com.google.api.client.http.GenericUrl;
import com.google.common.base.Preconditions;
import org.json.JSONArray;

import java.io.IOException;

public class CcbApi {

    private static final String BASE_URL = "https://api.ccbchurch.com";

    private static final String INDIVIDUALS_URI = "/individuals";

    private final RestClient client;

    public CcbApi(RestClient client) {
        this.client = Preconditions.checkNotNull(client, "client must not be null");
    }

    public JSONArray getIndividuals() throws IOException {
        var getIndividuals = createUrl(INDIVIDUALS_URI);
        return this.client.getJson(getIndividuals);
    }

    private static GenericUrl createUrl(String uri) {
        return new GenericUrl(BASE_URL + uri);
    }

}
