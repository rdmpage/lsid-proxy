package org.omg.lsid;

public interface LSIDMetadataService
    extends LSIDResolutionService {
	
    MetadataResponse getMetadata (LSID lsid, String[] acceptedFormats)
	throws org.omg.lsid.LSIDException;

    MetadataResponse getMetadataSubset (LSID lsid, String[] acceptedFormats,
					String selector)
	throws org.omg.lsid.LSIDException;
}
