package org.omg.lsid;

public interface LSIDResolutionDiscovery {
	
    LSIDAuthority[] getLSIDResolutionService (LSID lsid)
	throws org.omg.lsid.LSIDException;
}
