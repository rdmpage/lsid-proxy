package org.omg.lsid;

public interface LSIDAuthority {

    LSIDResolutionService[] getAvailableServices (LSID lsid)
	throws org.omg.lsid.LSIDException;
}
