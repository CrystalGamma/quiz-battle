import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;


/**
 * @author fbussmann
 *
 */
@SuppressWarnings( "nls" )
public class ConnectionHelper {
    private static final String BASE_URL = "http://localhost/";

    protected static String sendGET( final String endpoint ) throws IOException {
        HttpURLConnection conn = getConnection( endpoint );
        conn.setRequestMethod( "GET" );
        conn.setRequestProperty( "Accept", "application/json" );

        int responseCode = conn.getResponseCode();
        if ( responseCode == HttpURLConnection.HTTP_OK ) {
            return getResponseBody( conn );
        } else {
            System.err.println( "GET request not worked. Response was: " + conn.getResponseMessage() );
            return null;
        }
    }

    protected static String sendPOST( final String endpoint, final String json, final int expectedResponse,
            final String... token ) throws IOException {
        HttpURLConnection conn = getConnection( endpoint );
        conn.setRequestMethod( "POST" );
        if ( token.length == 1 ) {
            conn.setRequestProperty( "Authorization", token[0] );
        }
        conn.setRequestProperty( "Content-Type", "application/json; charset=UTF-8" );
        conn.setDoOutput( true );

        OutputStream out = conn.getOutputStream();
        out.write( json.getBytes( "utf-8" ) );
        out.close();

        int responseCode = conn.getResponseCode();
        if ( responseCode != expectedResponse ) {
            System.err.println( "POST request not worked. Response was: " + conn.getResponseMessage() + " w/ "
                    + getResponseBody( conn ) );
            return null;
        } else {
            return getResponseBody( conn );
        }
    }

    protected static HttpURLConnection getConnection( final String endpoint ) throws IOException {
        URL url = new URL( BASE_URL + endpoint );
        return (HttpURLConnection) url.openConnection();
    }

    protected static String getResponseBody( final HttpURLConnection conn ) throws IOException {
        BufferedReader in;
        if ( ( "" + conn.getResponseCode() ).startsWith( "2" ) ) {
            in = new BufferedReader( new InputStreamReader( conn.getInputStream() ) );
        } else {
            in = new BufferedReader( new InputStreamReader( conn.getErrorStream() ) );
        }
        String inputLine;

        StringBuffer response = new StringBuffer();
        while ( ( inputLine = in.readLine() ) != null ) {
            response.append( inputLine );
        }
        in.close();

        return response.toString();
    }
}
