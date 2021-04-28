package com.churchcommunitybuilder;

import com.google.api.client.auth.oauth2.AuthorizationCodeFlow;
import com.google.api.client.auth.oauth2.BearerToken;
import com.google.api.client.extensions.java6.auth.oauth2.AuthorizationCodeInstalledApp;
import com.google.api.client.extensions.jetty.auth.oauth2.LocalServerReceiver;
import com.google.api.client.googleapis.javanet.GoogleNetHttpTransport;
import com.google.api.client.http.GenericUrl;
import com.google.api.client.json.jackson2.JacksonFactory;
import com.google.api.client.util.store.FileDataStoreFactory;
import com.google.common.primitives.Ints;

import java.io.File;
import java.io.FileInputStream;
import java.io.IOException;
import java.security.GeneralSecurityException;
import java.util.Properties;

public class Main {

    private static final String STORED_CREDENTIALS_DIRECTORY_PATH = "tokens";
    private static final String STORED_CREDENTIALS_USER = "api_user";

    private static final String TOKEN_SERVER_URL = "https://api.ccbchurch.com/oauth/token";
    private static final String AUTHORIZATION_SERVER_URL = "https://oauth.ccbchurch.com/oauth/authorize";

    private static final String CONFIGURATION_NAME = "config.properties";
    private static final String KEY_CLIENT_ID = "client_id";
    private static final String KEY_CLIENT_SECRET = "client_secret";
    private static final String KEY_SUBDOMAIN = "subdomain";
    private static final String KEY_PORT = "port";
    private static final String DEFAULT_PORT = "8080";

    public static void main(String[] args) throws IOException, GeneralSecurityException {
        var properties = loadConfiguration();
        var client = createAuthorizedRestClient(properties);
        var api = new CcbApi(client);

        api.getIndividuals().forEach(System.out::println);
    }

    private static Properties loadConfiguration() throws IOException {
        Properties properties = new Properties();

        var workingDirectoryConfiguration = new File(CONFIGURATION_NAME);
        try (var inputStream = new FileInputStream(workingDirectoryConfiguration)) {
            properties.load(inputStream);
        }

        return properties;
    }

    private static RestClient createAuthorizedRestClient(Properties properties) throws GeneralSecurityException, IOException {
        var transport = GoogleNetHttpTransport.newTrustedTransport();

        var flow = createAuthorizationCodeFlow(properties);
        var receiver = createLocalServerReceiver(properties);
        var credentials = new AuthorizationCodeInstalledApp(flow, receiver).authorize(STORED_CREDENTIALS_USER);
        var requestFactory = transport.createRequestFactory(credentials);

        return new RestClient(requestFactory);
    }

    private static AuthorizationCodeFlow createAuthorizationCodeFlow(Properties properties) throws GeneralSecurityException, IOException {
        var method = BearerToken.authorizationHeaderAccessMethod();
        var transport = GoogleNetHttpTransport.newTrustedTransport();
        var jsonFactory = JacksonFactory.getDefaultInstance();

        var tokenServerUrl = new GenericUrl(TOKEN_SERVER_URL);

        var clientId = properties.getProperty(KEY_CLIENT_ID);
        var clientSecret = properties.getProperty(KEY_CLIENT_SECRET);
        var clientAuthentication = new ClientJsonAuthentication(clientId, clientSecret);

        var authorizationServerEncodedUrl = createAuthorizationServerEncodedUrl(properties);

        // WARNING: Do NOT save stored credentials in git!
        var dataDirectory = new File(STORED_CREDENTIALS_DIRECTORY_PATH);
        var dataStoreFactory = new FileDataStoreFactory(dataDirectory);

        return new AuthorizationCodeFlow.Builder(
                method, transport, jsonFactory, tokenServerUrl, clientAuthentication, clientId, authorizationServerEncodedUrl)
                .setDataStoreFactory(dataStoreFactory)
                .build();
    }

    private static String createAuthorizationServerEncodedUrl(Properties properties) {
        var authorizationServerUrl = new GenericUrl(AUTHORIZATION_SERVER_URL);
        var subdomain = properties.getProperty(KEY_SUBDOMAIN);
        authorizationServerUrl.put("subdomain", subdomain);

        return authorizationServerUrl.toString();
    }

    private static LocalServerReceiver createLocalServerReceiver(Properties properties) {
        var portString = properties.getProperty(KEY_PORT, DEFAULT_PORT);
        var port = Ints.tryParse(portString);

        return new LocalServerReceiver.Builder().setPort(port).build();
    }

}
