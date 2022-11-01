package org.omg.lsid;

import java.io.InputStream;

public interface LSIDDataService
    extends LSIDResolutionService {
	
    InputStream getData (LSID lsid)
	throws org.omg.lsid.LSIDException;
	
    byte[] getDataByRange (LSID lsid, int start, int length)
	throws org.omg.lsid.LSIDException;
}
