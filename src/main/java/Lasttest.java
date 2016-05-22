import java.io.IOException;


/**
 * @author fbussmann
 *
 */
public class Lasttest {

    public static void main( final String[] args ) throws IOException {
        final JsonParser jsonParser = new JsonParser();
        Thread status = new Thread( new StatusRunnable( jsonParser ) );
        status.start();
        for ( int i = 0; i < 10; i++ ) {
            new Thread( new PlayerRunnable( jsonParser ) ).start();
        }
    }

}
