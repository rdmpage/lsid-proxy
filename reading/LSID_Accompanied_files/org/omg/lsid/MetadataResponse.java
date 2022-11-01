package org.omg.lsid;

import java.io.InputStream;
import java.util.Date;

public interface MetadataResponse {
	
    String getFormat();

    Date getExpirationDate();

    InputStream getMetadata()
	throws LSIDException;

}
