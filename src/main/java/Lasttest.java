import java.io.IOException;


/**
 * @author fbussmann
 *
 */
public class Lasttest {

    public static void main( final String[] args ) throws IOException {
        final JsonParser jsonParser = new JsonParser();
        StatusRunnable statusRunnable = new StatusRunnable( jsonParser );
        Thread status = new Thread( statusRunnable );
        status.start();
        for ( int i = 0; i < 25; i++ ) {
            new Thread( new PlayerRunnable( statusRunnable, jsonParser ) ).start();
            new Thread( new GamesRunnable( statusRunnable, jsonParser ) ).start();
        }
    }

}
