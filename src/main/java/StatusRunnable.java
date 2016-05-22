import java.util.Map;


/**
 * @author fbussmann
 *
 */
@SuppressWarnings( { "rawtypes", "nls" } )
public class StatusRunnable implements Runnable {
    private final JsonParser jsonParser;

    public StatusRunnable( final JsonParser jsonParser ) {
        this.jsonParser = jsonParser;
    }

    /** {@inheritDoc} */
    @Override
    public void run() {
        while ( true ) {
            try {
                String json = ConnectionHelper.sendGET( "players/" );
                if ( json != null ) {
                    Map map = this.jsonParser.parseJson( json );
                    System.out.println( "There are already " + map.get( "count" ) + " players registered." );
                }
                Thread.sleep( 1000 );
            } catch ( Exception ex ) {
                ex.printStackTrace();
            }
        }
    }
}
