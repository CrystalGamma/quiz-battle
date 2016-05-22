import java.net.HttpURLConnection;
import java.util.Map;


/**
 * @author fbussmann
 *
 */
@SuppressWarnings( { "rawtypes", "nls" } )
public class PlayerRunnable implements Runnable {
    private final StatusRunnable status;
    private final JsonParser     jsonParser;

    public PlayerRunnable( final StatusRunnable status, final JsonParser jsonParser ) {
        this.status = status;
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
                if ( null != ConnectionHelper.sendPOST( "players/",
                        "{\"\":\"/schema/player\",\"name\":\"" + playername + "\",\"password\":\"test\"}",
                        HttpURLConnection.HTTP_CREATED ) ) {
                    this.status.increase( "players" );
                }
            }
        } catch ( Exception ex ) {
            ex.printStackTrace();
        }
    }
}
