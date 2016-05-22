import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.TimeUnit;


/**
 * @author fbussmann
 *
 */
@SuppressWarnings( { "rawtypes", "nls" } )
public class StatusRunnable implements Runnable {
    private final JsonParser     jsonParser;
    private Map<String, Integer> created = new HashMap<>();

    public StatusRunnable( final JsonParser jsonParser ) {
        this.jsonParser = jsonParser;
        this.created.put( "games", 0 );
        this.created.put( "players", 0 );
    }

    public void increase( final String key ) {
        this.created.put( key, this.created.get( key ) + 1 );
    }

    /** {@inheritDoc} */
    @Override
    public void run() {
        long start = System.currentTimeMillis();
        while ( true ) {
            try {
                Thread.sleep( 10000 );
                String json = ConnectionHelper.sendGET( "players/" );
                if ( json != null ) {
                    Map map = this.jsonParser.parseJson( json );

                    long timewentpast = System.currentTimeMillis() - start;
                    String timeframe = String.format( "%d min, %d sec", TimeUnit.MILLISECONDS.toMinutes( timewentpast ),
                            TimeUnit.MILLISECONDS.toSeconds( timewentpast )
                                    - TimeUnit.MINUTES.toSeconds( TimeUnit.MILLISECONDS.toMinutes( timewentpast ) ) );

                    System.out
                            .println( "----------------\nThere are already " + map.get( "count" )
                                    + " players registered. There have been\n" + "- " + this.created.get( "players" )
                                    + " players and\n- " + this.created.get( "games" ) + " games\ncreated in the last "
                                    + timeframe + ".\nThis means:\n"
                                    + String.format( "%d players/sec\n",
                                            this.created.get( "players" )
                                                    / TimeUnit.MILLISECONDS.toSeconds( timewentpast ) )
                            + String.format( "%d games/sec",
                                    this.created.get( "games" ) / TimeUnit.MILLISECONDS.toSeconds( timewentpast ) )
                            + "\n----------------" );
                }
            } catch ( Exception ex ) {
                ex.printStackTrace();
            }
        }
    }
}
