# Tools & Frameworks Reference

## Table of Contents

1. [Overview](#overview)
2. [MITRE ATT&CK Ecosystem](#mitre-attck-ecosystem)
3. [Command & Control (C2) Frameworks](#command--control-c2-frameworks)
4. [Exploitation Frameworks](#exploitation-frameworks)
5. [Credential Attacks](#credential-attacks)
6. [Phishing & Social Engineering](#phishing--social-engineering)
7. [OSINT & Reconnaissance](#osint--reconnaissance)
8. [AI/LLM Red Teaming Tools](#aillm-red-teaming-tools)
9. [Network Security Tools](#network-security-tools)
10. [Defensive Tools (Blue Team)](#defensive-tools-blue-team)
11. [Tool Selection Criteria](#tool-selection-criteria)
12. [Critical Reminders](#critical-reminders)

## Overview

Comprehensive reference for red teaming tools across cybersecurity and AI/LLM domains. Organized by function and use case.

## MITRE ATT&CK Ecosystem

### ATT&CK Navigator
- **Purpose**: Visual red team planning and heat mapping
- **URL**: https://mitre-attack.github.io/attack-navigator/
- **Features**:
  - Layer-based technique mapping
  - APT group TTP visualization
  - Detection coverage heat maps
  - Export to JSON, SVG, Excel
- **Use Case**: Plan red team operations, visualize detection gaps

### Atomic Red Team
- **Purpose**: Pre-built ATT&CK technique tests
- **Developer**: Red Canary (open source)
- **GitHub**: https://github.com/redcanaryco/atomic-red-team
- **Features**:
  - 200+ technique tests
  - Cross-platform (Windows, Linux, macOS)
  - ~5 minutes per test
  - Invoke-Atomic PowerShell framework
- **Use Case**: Quick technique validation, purple teaming

### CALDERA
- **Purpose**: Automated adversary emulation
- **Developer**: MITRE (open source)
- **GitHub**: https://github.com/mitre/caldera
- **Features**:
  - Autonomous red team operations
  - Plugin architecture (OT protocols, Atomic Red Team integration)
  - AI planning engine
  - Web-based management
- **Use Case**: Automated red team campaigns, continuous testing

### ATT&CK Evaluations
- **Purpose**: Independent product testing
- **URL**: https://attackevals.mitre-engenuity.org/
- **Features**:
  - Standardized adversary emulation
  - EDR/XDR evaluation
  - Public methodology and results
- **Use Case**: Evaluate security product effectiveness

## Command & Control (C2) Frameworks

### Cobalt Strike
- **Type**: Commercial ($3,500/user/year)
- **Strengths**: 
  - Industry standard for red teams and APTs
  - Malleable C2 profiles (traffic shaping)
  - Beacon implant with many post-exploitation features
  - Sleep obfuscation, AMSI bypass
- **Weaknesses**: 
  - Well-signatured by defenders
  - Expensive
  - Frequently cracked versions used by criminals (bad optics)
- **Use Case**: Professional red team engagements

### Sliver
- **Type**: Open source (GPL-3.0)
- **Developer**: BishopFox
- **GitHub**: https://github.com/BishopFox/sliver
- **Strengths**:
  - Modern architecture (Go-based)
  - Strong encryption (mTLS, WireGuard, HTTP(S))
  - Cross-platform implants
  - Multi-player support
  - Active development
- **Weaknesses**:
  - Less mature than Cobalt Strike
  - Smaller community
- **Use Case**: Budget-conscious red teams, modern C2 requirements

### Empire/Starkiller
- **Type**: Open source
- **GitHub**: https://github.com/BC-SECURITY/Empire
- **Strengths**:
  - PowerShell and Python agents
  - Modular post-exploitation
  - Starkiller GUI (user-friendly)
- **Weaknesses**:
  - PowerShell heavily monitored
  - Less stealthy than alternatives
- **Use Case**: Windows-heavy environments, PowerShell red teams

### Mythic
- **Type**: Open source
- **GitHub**: https://github.com/its-a-feature/Mythic
- **Strengths**:
  - Web-based collaborative UI
  - Plugin architecture for agents
  - Logging and reporting built-in
  - Docker-based deployment
- **Weaknesses**:
  - Requires setup effort
  - Less documentation than Cobalt Strike
- **Use Case**: Team-based red team operations, custom agent development

## Exploitation Frameworks

### Metasploit
- **Type**: Open source (Rapid7)
- **Website**: https://www.metasploit.com/
- **Strengths**:
  - 2,300+ exploits
  - Meterpreter post-exploitation framework
  - Database for session management
  - Extensive community modules
- **Weaknesses**:
  - Well-signatured by AV/EDR
  - Noisy by default
- **Use Case**: Exploitation, post-exploitation, penetration testing

### Covenant
- **Type**: Open source
- **GitHub**: https://github.com/cobbr/Covenant
- **Strengths**:
  - .NET-based C2
  - Web-based UI
  - Grunts (implants) with .NET in-memory execution
- **Weaknesses**:
  - Development slowed
  - Smaller community
- **Use Case**: .NET-focused red teams, post-exploitation

### Merlin
- **Type**: Open source
- **GitHub**: https://github.com/Ne0nd0g/merlin
- **Strengths**:
  - Go-based agents (cross-platform)
  - HTTP/2 C2 (stealthy)
  - QUIC support
- **Weaknesses**:
  - Command-line interface only
  - Less feature-rich than Cobalt Strike
- **Use Case**: Stealthy C2, cross-platform red teams

## Credential Attacks

### Mimikatz
- **Type**: Open source
- **Developer**: Benjamin Delpy
- **GitHub**: https://github.com/gentilkiwi/mimikatz
- **Strengths**:
  - Extract plaintext passwords, hashes, Kerberos tickets
  - Pass-the-hash, pass-the-ticket
  - Golden/Silver ticket creation
- **Weaknesses**:
  - Heavily signatured by AV/EDR
  - Requires admin privileges
- **Use Case**: Credential dumping, Kerberos attacks

### BloodHound
- **Type**: Open source
- **GitHub**: https://github.com/BloodHoundAD/BloodHound
- **Strengths**:
  - Active Directory attack path visualization
  - Graph-based analysis
  - Identifies shortest path to Domain Admin
- **Weaknesses**:
  - Requires SharpHound data collection (can be detected)
  - Learning curve for graph query language
- **Use Case**: Active Directory enumeration and attack planning

### Rubeus
- **Type**: Open source
- **GitHub**: https://github.com/GhostPack/Rubeus
- **Strengths**:
  - Kerberos abuse toolkit
  - Kerberoasting, AS-REP roasting
  - Ticket manipulation
- **Weaknesses**:
  - .NET executable (may be blocked by AppLocker)
- **Use Case**: Kerberos-based attacks in Active Directory

### Impacket
- **Type**: Open source (Python library)
- **GitHub**: https://github.com/fortra/impacket
- **Strengths**:
  - Python implementation of network protocols
  - SMB, MSRPC, LDAP, Kerberos
  - Standalone scripts (psexec.py, secretsdump.py, etc.)
- **Weaknesses**:
  - Python dependency (not always available on target)
- **Use Case**: Network protocol exploitation, lateral movement

## Phishing & Social Engineering

### Gophish
- **Type**: Open source
- **Website**: https://getgophish.com/
- **Strengths**:
  - Full-featured phishing framework
  - Email templates and landing pages
  - Campaign tracking and reporting
  - User-friendly web UI
- **Weaknesses**:
  - Server infrastructure required
  - Email deliverability challenges
- **Use Case**: Phishing campaigns, security awareness training

### Social-Engineer Toolkit (SET)
- **Type**: Open source (TrustedSec)
- **GitHub**: https://github.com/trustedsec/social-engineer-toolkit
- **Strengths**:
  - Automated phishing and credential harvesting
  - Mass mailer
  - QR code attacks
  - Wireless access point attacks
- **Weaknesses**:
  - Text-based menu interface
  - Requires setup
- **Use Case**: Multi-vector social engineering campaigns

### EvilNginx
- **Type**: Open source
- **GitHub**: https://github.com/kgretzky/evilginx2
- **Strengths**:
  - Adversary-in-the-middle (AitM) phishing
  - Bypass 2FA (session hijacking)
  - Reverse proxy architecture
- **Weaknesses**:
  - Complex setup
  - Requires custom phishlets
- **Use Case**: Advanced phishing bypassing MFA

## OSINT & Reconnaissance

### theHarvester
- **Type**: Open source
- **GitHub**: https://github.com/laramies/theHarvester
- **Strengths**:
  - Email, subdomain, name enumeration
  - Multiple search engines (Google, Bing, Shodan, etc.)
  - Fast reconnaissance
- **Weaknesses**:
  - API rate limits
- **Use Case**: Initial OSINT, target profiling

### Shodan
- **Type**: Commercial (free tier available)
- **Website**: https://www.shodan.io/
- **Strengths**:
  - Internet-connected device search engine
  - Identify exposed services and vulnerabilities
  - API access
- **Weaknesses**:
  - Paid features for advanced queries
- **Use Case**: External asset discovery, vulnerability research

### Amass
- **Type**: Open source (OWASP)
- **GitHub**: https://github.com/owasp-amass/amass
- **Strengths**:
  - DNS enumeration and network mapping
  - Subdomain discovery
  - ASN mapping
- **Weaknesses**:
  - Slow for large domains
- **Use Case**: External attack surface mapping

### SpiderFoot
- **Type**: Open source
- **Website**: https://www.spiderfoot.net/
- **Strengths**:
  - Automated OSINT collection
  - 200+ data sources
  - Web UI and correlation engine
- **Weaknesses**:
  - Resource-intensive
- **Use Case**: Comprehensive OSINT investigations

## AI/LLM Red Teaming Tools

### DeepTeam
- **Type**: Open source (Python framework)
- **GitHub**: https://github.com/confident-ai/deepteam
- **Strengths**:
  - 40+ vulnerabilities (OWASP Top 10 LLM aligned)
  - 10+ attack methods (single-turn & multi-turn)
  - LLM-as-judge evaluation
  - YAML-based configuration
  - CLI and programmatic API
- **Weaknesses**:
  - Requires API keys for evaluation models
  - Python dependency
- **Use Case**: Comprehensive LLM security testing, CI/CD integration

### Promptfoo
- **Type**: Open source
- **Website**: https://www.promptfoo.dev/
- **GitHub**: https://github.com/promptfoo/promptfoo
- **Strengths**:
  - 20+ red team vulnerability categories
  - Custom evaluation metrics
  - Supports multiple LLM providers
  - Web UI for results visualization
- **Weaknesses**:
  - Node.js dependency
- **Use Case**: LLM application testing, prompt optimization

### FLIRT (Feedback Loop In-context Red Teaming)
- **Type**: Research tool
- **Paper**: https://arxiv.org/abs/2308.04265
- **Strengths**:
  - Iterative attack refinement
  - Uses feedback to improve attacks
  - High success rate
- **Weaknesses**:
  - Research prototype (not production-ready)
- **Use Case**: Academic research, advanced red teaming

### AdvPrompter
- **Type**: Research tool
- **Strengths**:
  - LLM-based adversarial prompt generation
  - Optimizes for effectiveness and speed
  - Generates human-readable attacks
- **Weaknesses**:
  - Research prototype
- **Use Case**: Automated attack generation research

### GRT (Gandalf Red Team)
- **Type**: Community challenge platform
- **Website**: https://gandalf.lakera.ai/
- **Strengths**:
  - Gamified LLM red teaming
  - Levels of difficulty
  - Community leaderboard
- **Weaknesses**:
  - Limited to specific scenarios
- **Use Case**: LLM security training, skill building

### JailbreakBench
- **Type**: Open benchmark
- **Website**: https://jailbreakbench.github.io/
- **Strengths**:
  - Standardized jailbreak evaluation
  - Public leaderboard
  - Reproducible testing
- **Weaknesses**:
  - Limited to jailbreak testing
- **Use Case**: LLM robustness benchmarking

## Network Security Tools

### Nmap
- **Type**: Open source
- **Website**: https://nmap.org/
- **Strengths**:
  - Port scanning and service detection
  - OS fingerprinting
  - NSE (Nmap Scripting Engine) for automation
- **Use Case**: Network reconnaissance, vulnerability scanning

### Burp Suite
- **Type**: Commercial (free Community edition)
- **Website**: https://portswigger.net/burp
- **Strengths**:
  - Web application security testing
  - Proxy, scanner, intruder, repeater
  - Extensible with BApps
- **Use Case**: Web application penetration testing

### Wireshark
- **Type**: Open source
- **Website**: https://www.wireshark.org/
- **Strengths**:
  - Network protocol analyzer
  - Packet capture and dissection
  - Display filters and analysis
- **Use Case**: Network traffic analysis, protocol debugging

## Defensive Tools (Blue Team)

### Velociraptor
- **Type**: Open source
- **Website**: https://www.rapid7.com/products/velociraptor/
- **Strengths**:
  - Endpoint visibility and forensics
  - Hunt malicious activity
  - Incident response platform
- **Use Case**: Detection engineering, threat hunting

### Sigma
- **Type**: Open source (detection rule format)
- **GitHub**: https://github.com/SigmaHQ/sigma
- **Strengths**:
  - Generic signature format for SIEM
  - 3,000+ detection rules
  - Convert to Splunk, Elastic, QRadar
- **Use Case**: Detection rule development and sharing

### YARA
- **Type**: Open source
- **Website**: https://virustotal.github.io/yara/
- **Strengths**:
  - Malware identification and classification
  - Pattern matching for files and processes
  - Widely adopted
- **Use Case**: Malware detection, threat intelligence

## Tool Selection Criteria

### For Cybersecurity Red Teaming
- **Initial Access**: Metasploit, Cobalt Strike, custom exploits
- **C2 Infrastructure**: Sliver (budget), Cobalt Strike (professional), Mythic (team)
- **Credential Attacks**: Mimikatz, BloodHound, Rubeus, Impacket
- **Phishing**: Gophish (standard), EvilNginx (advanced MFA bypass)
- **OSINT**: theHarvester (quick), Amass (comprehensive), Shodan (external)

### For AI/LLM Red Teaming
- **Comprehensive Testing**: DeepTeam (production), Promptfoo (development)
- **Research**: FLIRT, AdvPrompter, academic frameworks
- **Benchmarking**: JailbreakBench, public leaderboards
- **Training**: GRT (Gandalf), capture-the-flag platforms

## Critical Reminders

- **Licensing**: Verify tool licenses (commercial vs. open source)
- **Legal Use**: Only use tools for authorized red team operations
- **Detection**: Most tools are well-signatured; customize for stealth
- **Updates**: Tools evolve rapidly; stay current with latest versions
- **Operational Security**: Protect red team infrastructure and tool chains
- **Blue Team Value**: Share tool knowledge with defenders for detection engineering
