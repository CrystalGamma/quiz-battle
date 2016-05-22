import java.io.IOException;
import java.util.Map;

import javax.script.ScriptEngine;
import javax.script.ScriptEngineManager;
import javax.script.ScriptException;


/**
 * @author abien
 *
 *         CONVERTING JSON TO MAP WITH JAVA 8 WITHOUT DEPENDENCIES
 *         http://www.adam-bien.com/roller/abien/entry/converting_json_to_map_with
 *
 *         Starting with JDK 8u60+ the built-in Nashorn engine is capable to convert Json content into java.util.Map. No
 *         external dependencies are required for parsing:
 *
 */
@SuppressWarnings( { "rawtypes", "nls" } )
public class JsonParser {
    private ScriptEngine engine;

    public JsonParser() {
        ScriptEngineManager sem = new ScriptEngineManager();
        this.engine = sem.getEngineByName( "javascript" );
    }

    public Map parseJson( final String json ) throws IOException, ScriptException {
        String script = "Java.asJSONCompatible(" + json + ")";
        Object result = this.engine.eval( script );
        return (Map) result;
    }
}
