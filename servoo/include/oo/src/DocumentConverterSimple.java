import com.sun.star.bridge.XUnoUrlResolver;
import com.sun.star.lang.XMultiServiceFactory;
import com.sun.star.lang.XComponent;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.uno.XComponentContext;
import com.sun.star.uno.UnoRuntime;
import com.sun.star.frame.XComponentLoader;
import com.sun.star.frame.XStorable;
import com.sun.star.beans.PropertyValue;
import com.sun.star.beans.XPropertySet;
//import com.sun.star.text.XTextDocument;
//import com.sun.star.text.XText;
//import com.sun.star.text.XTextRange;
//import com.sun.star.text.XTextContent;
//import com.sun.star.beans.XPropertyState;
//import com.sun.star.beans.PropertyState;
//import com.sun.star.lang.XServiceInfo;
//import com.sun.star.container.XEnumeration;
//import com.sun.star.container.XEnumerationAccess;

import java.io.File;
//import java.io.FileFilter;


/** The class <CODE>DocumentConverterSimple</CODE> allows you to convert all documents in
 * a given directory and in its subdirectories to a given type. A converted
 * document will be created in the same directory as the origin document.
 *
 */
public class DocumentConverterSimple {

    /** Containing the loaded documents
     */
    static XComponentLoader xcomponentloader = null;
    /** Containing the given type to convert to
     */
    static String stringConvertType = "";
    /** Containing the given extension
     */
    static String stringExtension = "";
    /** Containing the current file or directory
     */
    static String indent = "";
  
    /** Traversing the given directory recursively and converting their files to the
     * favoured type if possible
     * @param fileDirectory Containing the directory
     */

    static void convertFile( File fileName ) {
	// Testing, if the file is a directory, and if so, it throws an exception
	// Converting the document to the favoured type
	try {
	    // Composing the URL by replacing all backslashs
	    String stringUrl = "file:///"
		+ fileName.getAbsolutePath().replace( '\\', '/' );

	    // Ces propriétés permettent de charger le document sans montrer la fenêtre
	    // repris de cyberthese
	    PropertyValue propertyvalue[] = new PropertyValue[1];
	    // temporaire
	    propertyvalue[0] = new PropertyValue();
	    propertyvalue[0].Name = "Hidden";
	    propertyvalue[0].Value = new Boolean(true);
	    //          
	    // Loading the wanted document
	    //	    System.out.println("Loading the document" + stringUrl + " <br>\n");
	    XComponent xComponent=
		DocumentConverterSimple.xcomponentloader.loadComponentFromURL(
									stringUrl, "_blank", 0, propertyvalue );
	    // voila xComponent contient mon document
	    //System.out.println("Loaded <br>\n");

	    // repris de cyberthese
	    // Si l'URL n'est pas bonne, on aura un null ici...
	    if ( xComponent == null ) {
		System.err.println("failed to load the document " + stringUrl + " in OpenOffice");	    	
		System.exit(1);
	    }

	    // enregistrement du fichier
	    // Getting an object that will offer a simple way to store a document to a URL.
	    XStorable xstorable =
		( XStorable ) UnoRuntime.queryInterface( XStorable.class,
							 xComponent );
          
	    // Preparing properties for converting the document
	    propertyvalue = new PropertyValue[ 2 ];
	    // Setting the flag for overwriting
	    propertyvalue[ 0 ] = new PropertyValue();
	    propertyvalue[ 0 ].Name = "Overwrite";
	    propertyvalue[ 0 ].Value = new Boolean(true);
	    // Setting the filter name
	    propertyvalue[ 1 ] = new PropertyValue();
	    propertyvalue[ 1 ].Name = "FilterName";
	    propertyvalue[ 1 ].Value = DocumentConverterSimple.stringConvertType;
          
	    // Appending the favoured extension to the origin document name
	    stringUrl = stringUrl + "." + DocumentConverterSimple.stringExtension;
          
	    // Storing and converting the document
	    xstorable.storeAsURL( stringUrl, propertyvalue );
          
	    // Getting the method dispose() for closing the document
	    XComponent xcomponent =
		( XComponent ) UnoRuntime.queryInterface( XComponent.class,
							  xstorable );
          
	    // Closing the converted document
	    xcomponent.dispose();
        }
        catch( Exception exception ) {
	    exception.printStackTrace();
        }
	//System.out.println("Finished saving/converting<br>");
    }
  
    /** Connecting to the office with the component UnoUrlResolver and calling the
     * static method traverse
     * @param args The array of the type String contains the directory, in which all files should be
     * converted, the favoured converting type and the wanted extension
     */
    public static void main( String args[] ) {
	try {
	    /** Le port par défaut pour contacter le serveur. Repris de CyberThese*/
	    String port = "9303";

	    /** Le serveur par défaut à contacter. Repris de CyberThese*/
	    String host = "localhost";

	    /** L'endroit où se trouve l'exécutable OpenOffice. Repris de CyberThese*/
	    String ooExecutable = "/usr/local/OpenOffice.org1.1.0/program/soffice";

	    if ( args.length < 5 ) {
		System.out.println( 
				   "Document plus a jour"+
				   "usage: java -classpath <see make> " +
				   "DocumentConverterSimple \"<file to convert>\"" +
				   " \"<type to convert to>\" \"<extension>\" \"<host>\" \"<port>\"" );
		System.out.println( "\ne.g.:" );
		System.out.println(
				   "java -classpath <see make> " +
				   "DocumentConverterSimple" + " \"c:/myoffice\" \"swriter: MS Word 97\" \"doc\"" );
		System.exit(1);
	    }
      
	    // It is possible to use a different connection string, passed as argument
      // Ghislain: It is possible to use a different openoffice path passed as argument
	    host= args[3];
	    port= args[4];
      
	    /* Bootstraps a component context with the jurt base components
	       registered. Component context to be granted to a component for running.
	       Arbitrary values can be retrieved from the context. */
	    XComponentContext xComponentContext =
		com.sun.star.comp.helper.Bootstrap.createInitialComponentContext( null );
      
	    /* Gets the service manager instance to be used (or null). This method has
	       been added for convenience, because the service manager is a often used
	       object. */
	    XMultiComponentFactory xMultiComponentFactory =
		xComponentContext.getServiceManager();
      
	    /* Creates an instance of the component UnoUrlResolver which
	       supports the services specified by the factory. */
	    Object objectUrlResolver = xMultiComponentFactory.createInstanceWithContext(
											"com.sun.star.bridge.UnoUrlResolver", xComponentContext );
      
	    // Create a new url resolver
	    XUnoUrlResolver xurlresolver = ( XUnoUrlResolver )
		UnoRuntime.queryInterface( XUnoUrlResolver.class,
					   objectUrlResolver );

	    // repris de cyberthese
	    // On détermine l'adresse du serveur et du service
	    String OOConnect = "socket,host=" + host + ",port=" + port + ";urp";

	    // On fait une tentative de connection ; si elle échoue, on essaie de démarrer le serveur
	    Object objectInitial = null;
	    try {
		objectInitial = xurlresolver.resolve("uno:" + OOConnect + ";StarOffice.ServiceManager");
	    }
	    catch (Exception e) {
		// On tente de démarrer le serveur OpenOffice
		// La commande est telle que :
		// soffice -accept=socket,host=localhost,port=9302;urp;StarOffice.ServiceManager
		//String arg = " -accept=socket,host=" + host + ",port=" + port + ";urp;StarOffice.ServiceManager";
		System.err.println("faile to connect OpenOffice server with the following connection parameters: " + OOConnect);
		System.exit(1); // temporaire... il faudrait essayer de demarrer OO
		//Process proc =  Runtime.getRuntime().exec(ooExecutable + arg);		// On va attendre 15 secondes le démarrage correct de OO
		//Thread.sleep(15000);	  
		// Et on essaie de nouveau, s'il y a toujours erreur tant pis...		//objectInitial = xurlresolver.resolve("uno:" + OOConnect + ";StarOffice.ServiceManager");
	    }
      
	    // supprimer de OO-SDK, car cyberthese essaie de demarrer le serveur avant de cracher
	    // Resolves an object that is specified as follow:
	    // uno:<connection description>;<protocol description>;<initial object name>
	    //Object objectInitial = xurlresolver.resolve( sConnectionString );
      
	    // Create a service manager from the initial object
	    xMultiComponentFactory = ( XMultiComponentFactory )
		UnoRuntime.queryInterface( XMultiComponentFactory.class, objectInitial );
      
	    // Query for the XPropertySet interface.
	    XPropertySet xpropertysetMultiComponentFactory = ( XPropertySet )
		UnoRuntime.queryInterface( XPropertySet.class, xMultiComponentFactory );
      
	    // Get the default context from the office server.
	    Object objectDefaultContext =
		xpropertysetMultiComponentFactory.getPropertyValue( "DefaultContext" );
      
	    // Query for the interface XComponentContext.
	    xComponentContext = ( XComponentContext ) UnoRuntime.queryInterface(
										XComponentContext.class, objectDefaultContext );
      
	    /* A desktop environment contains tasks with one or more
	       frames in which components can be loaded. Desktop is the
	       environment for components which can instanciate within
	       frames. */
	    xcomponentloader = ( XComponentLoader )
		UnoRuntime.queryInterface( XComponentLoader.class,
					   xMultiComponentFactory.createInstanceWithContext(
											    "com.sun.star.frame.Desktop", xComponentContext ) );
      
	    // Getting the given starting directory
	    File file = new File(args[ 0 ]);
      
	    // Getting the given type to convert to
	    stringConvertType = args[ 1 ];
      
	    // Getting the given extension that should be appended to the origin document
	    stringExtension = args[ 2 ];
      
	    // Starting the conversion of documents in the given directory and subdirectories
	    //      traverse( file );
	    convertFile( file );
      
	    System.exit(0);
	}
	catch( Exception exception ) {
	    System.err.println( exception );
	}
    }
}
