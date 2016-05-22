import java.net.HttpURLConnection;
import java.util.Map;


/**
 * @author fbussmann
 *
 */
@SuppressWarnings( { "rawtypes", "nls" } )
public class GamesRunnable implements Runnable {
    private final StatusRunnable status;
    private final JsonParser     jsonParser;
    private String               token;

    public GamesRunnable( final StatusRunnable status, final JsonParser jsonParser ) {
        this.status = status;
        this.jsonParser = jsonParser;
    }

    /** {@inheritDoc} */
    @Override
    public void run() {
        try {
            while ( true ) {
                if ( this.token == null ) {
                    String auth = ConnectionHelper.sendPOST( "auth", "{\"user\":\"admin\",\"password\":\"admin\"}",
                            200 );
                    if ( auth != null ) {
                        Map map = this.jsonParser.parseJson( auth );
                        this.token = map.get( "token" ).toString();
                    }
                }

                if ( null != ConnectionHelper.sendPOST( "games/",
                        "{\"\":\"/schema/game?new\",\"players_\": [\"/players/1\"],\"rounds\":5,\"turns\":\"3\",\"timelimit\":10,\"roundlimit\":172800,\"dealingrule\":\"/players/1\"}",
                        HttpURLConnection.HTTP_CREATED, this.token ) ) {
                    this.status.increase( "games" );
                }
            }
        } catch ( Exception ex ) {
            ex.printStackTrace();
        }
    }
}
