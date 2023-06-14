<?xml version='1.0' encoding='utf-8'?>
<xsl:stylesheet version='1.0'
	xmlns:xsl='http://www.w3.org/1999/XSL/Transform'
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:dcterms="http://purl.org/dc/terms/"
	xmlns:tn="http://rs.tdwg.org/ontology/voc/TaxonName#"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:owl="http://www.w3.org/2002/07/owl#"
	xmlns:tm="http://rs.tdwg.org/ontology/voc/Team#"
	xmlns:tcom="http://rs.tdwg.org/ontology/voc/Common#"
	xmlns:p="http://rs.tdwg.org/ontology/voc/Person#"
	xmlns:tpub="http://rs.tdwg.org/ontology/voc/PublicationCitation#"   

exclude-result-prefixes="dc dcterms rdf owl tn tm tcom p tpub"
  
>
	<xsl:output method='html' encoding='utf-8' indent='yes' />
	
	<xsl:template name="replace">
		<xsl:param name="string"/>
		<xsl:param name="substring"/>
		<xsl:param name="replacement"/>
		<xsl:choose>
			<xsl:when test="contains($string, $substring)">
				<xsl:value-of select="substring-before($string, $substring)"/>
				<xsl:value-of select="$replacement"/>
				<xsl:call-template name="replace">
					<xsl:with-param name="substring" select="$substring"/>
					<xsl:with-param name="replacement" select="$replacement"/>
					<xsl:with-param name="string" select="substring-after($string, $substring)"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$string"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	<xsl:template match="/">
		<xsl:text disable-output-escaping='yes'>&lt;!DOCTYPE html&gt;</xsl:text>
		<html>
			<head>
				 <link type="text/css" href="main.css" rel="stylesheet" />
				 <meta name="theme-color" content="#1a5d8d" />	
				 
				 
				 <!--
				 <meta name="twitter:card" content="summary_large_image" />
				 <meta name="twitter:image" content="https://lsid.io/images/lsid.png" />
				 <xsl:if test="//tn:TaxonName|//rdf:Description|//tpub:PublicationCitation|//p:Person">
					<meta name="twitter:title">
						<xsl:attribute name="content">
							<xsl:value-of select="//dc:Title" />
							<xsl:value-of select="//dc:title" />
							<xsl:value-of select="//dcterms:title" />							 	
						</xsl:attribute>
					</meta>
				</xsl:if>
				 
				 <xsl:if test="starts-with(//@rdf:about, 'urn')">
					<xsl:if test="position() = 1">
						<meta name="twitter:description">
							<xsl:attribute name="content">
							 	<xsl:value-of select="//@rdf:about" />
							</xsl:attribute>
						</meta>
					</xsl:if>
				</xsl:if>
				
				-->
				
				<meta name="og:image" content="https://lsid.io/images/lsid.png" />
				
				<xsl:if test="//tn:TaxonName|//rdf:Description|//tpub:PublicationCitation|//p:Person">
					<meta name="og:title">
						<xsl:attribute name="content">
							<xsl:value-of select="//dc:Title" />
							<xsl:value-of select="//dc:title" />
							<xsl:value-of select="//dcterms:title" />							 	
						</xsl:attribute>
					</meta>
				</xsl:if>
				
				 <xsl:if test="starts-with(//@rdf:about, 'urn')">
				 	<xsl:if test="position() = 1">
						<meta name="og:description">
							<xsl:attribute name="content">
								<xsl:value-of select="//@rdf:about" />
							</xsl:attribute>
						</meta>
					</xsl:if>
				</xsl:if>			 
				 
			</head>
			<body>
				<div><a href="./">Home</a></div>
				<xsl:apply-templates select="//tn:TaxonName|//rdf:Description|//tpub:PublicationCitation|//p:Person"/>
			</body>
		</html>
	</xsl:template>
	
	
	<xsl:template match="//tn:TaxonName|//rdf:Description|//tpub:PublicationCitation|//p:Person">
		<h1>
			<xsl:value-of select="dc:Title|dc:title|dcterms:title" />
		</h1>
		
		<xsl:if test="starts-with(@rdf:about, 'urn')">
			<xsl:if test="position() = 1">
				<p>
					<xsl:text>Data for the Life Science Identifier (LSID) </xsl:text>
					<a>
						<xsl:attribute name="href">
							<xsl:text>./</xsl:text>
							<xsl:value-of select="@rdf:about" />
						</xsl:attribute>
						<xsl:value-of select="@rdf:about" />
					</a>
					<xsl:text></xsl:text>
					<xsl:text> (view </xsl:text>
					<a>
						<xsl:attribute name="href">
							<xsl:text>./</xsl:text>
							<xsl:value-of select="@rdf:about" />
							<xsl:text>&amp;format=xml</xsl:text>
						</xsl:attribute>
						<xsl:text>XML</xsl:text>
					</a>
					<xsl:text>)</xsl:text>
					<xsl:text>.</xsl:text>
				</p>
			</xsl:if>
		</xsl:if>
		
		<table>
			<xsl:for-each select="*">
				<tr>
					<td class="key">
						<xsl:value-of select="local-name()" />
					</td>
					<td>
					<xsl:text>➪</xsl:text>
					</td>
					<td>
						<xsl:choose>
							<xsl:when test="@rdf:nodeID">
								<xsl:value-of select="@rdf:nodeID" />
							</xsl:when>
							<xsl:when test="@rdf:resource">
								<xsl:choose>
									<!-- other LSIDs -->
									<xsl:when test="local-name()='basionymFor'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='hasBasionym'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='acceptedNameUsageID'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='scientificNameID'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
											
										</a>
									</xsl:when>
									<xsl:when test="local-name()='parentNameUsageID'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='isReplacedBy'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='replaces'">
										<a>
											<xsl:attribute name="href">
												<xsl:text>./</xsl:text>
												<xsl:value-of select="@rdf:resource" />
												<xsl:text>+</xsl:text>
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									
									<!-- external links -->
									
									<xsl:when test="local-name()='type'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>

									<xsl:when test="local-name()='publicationType'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									
									
									<xsl:when test="local-name()='creator'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='hasInformation'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='nomenclaturalCode'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='rank'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									<xsl:when test="local-name()='seeAlso'">
										<a class="external" target="_new">
											<xsl:attribute name="href">
												<xsl:value-of select="@rdf:resource" />
											</xsl:attribute>
											<xsl:value-of select="@rdf:resource" />
										</a>
									</xsl:when>
									
									<!-- IPNI versioned LSIDs don't work so output as text-->

									<xsl:when test="local-name()='versionedAs'">
										<xsl:value-of select="@rdf:resource" />
									</xsl:when>
									

									<!-- Zoobank Publication LSIDs don't work so output as text-->

									<xsl:when test="local-name()='parentPublication'">
										<xsl:value-of select="@rdf:resource" />
									</xsl:when>
									

									<xsl:otherwise>
										<xsl:text>[unknown]</xsl:text>
									</xsl:otherwise>
									
								</xsl:choose>
							</xsl:when>
							<xsl:otherwise>
								<xsl:choose>
									<xsl:when test="starts-with(., 'http')">
										<a class="external" target="_new">
											<xsl:variable name="string-with-escaped-ampersand">
												<xsl:call-template name="replace">
													<!-- note that we use normalize-space to remove trailing whitespace -->
													<xsl:with-param name="string" select="normalize-space(.)"/>
													<xsl:with-param name="substring">&amp;amp;</xsl:with-param>
													<xsl:with-param name="replacement">&amp;</xsl:with-param>
												</xsl:call-template>
											</xsl:variable>
											<xsl:attribute name="href">
												<xsl:value-of select="$string-with-escaped-ampersand"/>
											</xsl:attribute>
											<xsl:value-of select="$string-with-escaped-ampersand"/>
										</a>
									</xsl:when>
									<xsl:otherwise>
										<xsl:value-of select="." />
									</xsl:otherwise>
								</xsl:choose>
							</xsl:otherwise>
						</xsl:choose>
					</td>
				</tr>
			</xsl:for-each>
		</table>
	</xsl:template>
	
</xsl:stylesheet>