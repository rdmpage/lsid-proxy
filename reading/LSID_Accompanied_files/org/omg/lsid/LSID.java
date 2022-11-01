package org.omg.lsid;

public interface LSID {
	
    String getLSID();

    String getAuthority();

    String getNamespace();

    String getObjectId();

    String getRevision();
}

