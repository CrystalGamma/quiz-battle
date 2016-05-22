import java.net.HttpURLConnection;
import java.util.Map;


/**
 * @author fbussmann
 *
 */
@SuppressWarnings( { "rawtypes", "nls" } )
public class PlayerRunnable implements Runnable {
    private final JsonParser jsonParser;

    public PlayerRunnable( final JsonParser jsonParser ) {
        this.jsonParser = jsonParser;
    }

    /** {@inheritDoc} */
    @Override
    public void run() {
        try {
            while ( true ) {
                String json = ConnectionHelper.sendGET( "players/" );
                String playername = "player";
                if ( json != null ) {
                    Map map = this.jsonParser.parseJson( json );
                    playername += map.get( "count" );
                }
                playername += System.currentTimeMillis();
                ConnectionHelper.sendPOST( "players/",
                        "{\"\":\"/schema/player\",\"name\":\"" + playername + "\",\"password\":\"test\"}",
                        HttpURLConnection.HTTP_CREATED );
            }
        } catch ( Exception ex ) {
            ex.printStackTrace();
        }
    }
}
