package com.example.nutrisaur11;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.*;
import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import android.util.Base64;

public class FatSecretClient {

    // FatSecret API credentials
    private static final String CLIENT_ID = "63c29de913a54b108f99106d9d1b3c5e";
    private static final String CLIENT_SECRET = "45a16d6866d14f949e6be311d1970d44";
    private static final String API_URL = "https://platform.fatsecret.com/rest/server.api";

    public static String call(String method, Map<String, String> params) throws Exception {
        Map<String, String> oauthParams = new TreeMap<>();
        oauthParams.put("method", method);
        oauthParams.put("format", "json");
        oauthParams.put("oauth_consumer_key", CLIENT_ID);
        oauthParams.put("oauth_nonce", UUID.randomUUID().toString().replaceAll("-", ""));
        oauthParams.put("oauth_signature_method", "HMAC-SHA1");
        oauthParams.put("oauth_timestamp", String.valueOf(System.currentTimeMillis() / 1000));
        oauthParams.put("oauth_version", "1.0");

        if (params != null) {
            oauthParams.putAll(params);
        }

        // Create signature base string
        StringBuilder paramString = new StringBuilder();
        for (Map.Entry<String, String> entry : oauthParams.entrySet()) {
            if (paramString.length() > 0) paramString.append("&");
            paramString.append(percentEncode(entry.getKey()))
                       .append("=")
                       .append(percentEncode(entry.getValue()));
        }

        String baseString = "GET&" + percentEncode(API_URL) + "&" + percentEncode(paramString.toString());

        // Sign with client secret
        String signingKey = CLIENT_SECRET + "&";
        String signature = hmacSha1(baseString, signingKey);

        // Build final URL
        String url = API_URL + "?" + paramString.toString() + "&oauth_signature=" + percentEncode(signature);

        // Execute HTTP GET
        HttpURLConnection conn = (HttpURLConnection) new URL(url).openConnection();
        conn.setRequestMethod("GET");
        conn.setConnectTimeout(10000);
        conn.setReadTimeout(10000);

        BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
        StringBuilder response = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            response.append(line);
        }
        reader.close();

        return response.toString();
    }

    private static String hmacSha1(String value, String key) throws Exception {
        SecretKeySpec signingKey = new SecretKeySpec(key.getBytes("UTF-8"), "HmacSHA1");
        Mac mac = Mac.getInstance("HmacSHA1");
        mac.init(signingKey);
        byte[] rawHmac = mac.doFinal(value.getBytes("UTF-8"));
        return Base64.encodeToString(rawHmac, Base64.NO_WRAP);
    }

    private static String percentEncode(String s) throws Exception {
        return URLEncoder.encode(s, "UTF-8")
                .replace("+", "%20")
                .replace("*", "%2A")
                .replace("%7E", "~");
    }
}
