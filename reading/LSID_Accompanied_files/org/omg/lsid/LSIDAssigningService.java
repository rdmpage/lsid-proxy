package org.omg.lsid;

import java.util.Properties;

public interface LSIDAssigningService {
    LSID assignLSID (String authority, String namespace,
		     Properties property_list)
	throws LSIDException;
    LSID assignLSIDFromList (Properties property_list,
			     LSID[] list_of_suggested_ids)
	throws LSIDException;
    String getLSIDPattern (String authority, String namespace,
			   Properties property_list)
	throws LSIDException;
    String getLSIDPatternFromList (Properties property_list,
				   String[] list_of_suggested_patterns)
	throws LSIDException;
    LSID assignLSIDForNewRevision (LSID previous_identifier)
	throws LSIDException;
    String[] getAllowedPropertyNames();
    String[][] getAuthoritiesAndNamespaces();
}
