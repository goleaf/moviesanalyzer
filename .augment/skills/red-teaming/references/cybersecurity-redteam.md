# Cybersecurity Red Teaming

## Table of Contents

1. [Overview](#overview)
2. [MITRE ATT&CK Framework Integration](#mitre-attck-framework-integration)
   - 14 ATT&CK Tactics
   - Using ATT&CK for Red Teaming
3. [7-Phase Cybersecurity Red Team Methodology](#7-phase-cybersecurity-red-team-methodology)
   - Phase 1: Intelligence Gathering
   - Phase 2: Vulnerability Detection
   - Phase 3: Exploitation & Initial Access
   - Phase 4: Lateral Movement
   - Phase 5: Privilege Escalation
   - Phase 6: Persistence
   - Phase 7: Objective Achievement & Reporting
4. [Purple Team Practices](#purple-team-practices)
5. [Tools & Frameworks](#tools--frameworks)
6. [Critical Reminders](#critical-reminders)

## Overview

This reference provides detailed methodology for traditional cybersecurity red teaming, focusing on network penetration, infrastructure compromise, and adversary emulation using the MITRE ATT&CK framework.

## MITRE ATT&CK Framework Integration

### What is MITRE ATT&CK?

MITRE ATT&CK (Adversarial Tactics, Techniques, and Common Knowledge) is a globally-accessible knowledge base of adversary behaviors based on real-world observations.

**Components:**
- **Tactics**: "Why" - Tactical goals (e.g., Initial Access, Persistence, Privilege Escalation)
- **Techniques**: "How" - Methods to achieve tactics (e.g., Spear Phishing, DLL Injection)
- **Procedures**: Technical implementation details (specific commands, tools)

### 14 ATT&CK Tactics

1. **Reconnaissance**: Gather information to plan operations
2. **Resource Development**: Establish resources for operations (infrastructure, tools)
3. **Initial Access**: Gain foothold in target network
4. **Execution**: Run malicious code
5. **Persistence**: Maintain access across reboots/credential changes
6. **Privilege Escalation**: Gain higher-level permissions
7. **Defense Evasion**: Avoid detection by security tools
8. **Credential Access**: Steal usernames, passwords, tokens
9. **Discovery**: Learn about internal environment
10. **Lateral Movement**: Move through victim network
11. **Collection**: Gather data of interest
12. **Command & Control**: Communicate with compromised systems
13. **Exfiltration**: Steal data from network
14. **Impact**: Disrupt availability, integrity, or data destruction

### Using ATT&CK for Red Teaming

**Step 1: Select Target APT Group**
- Identify adversary groups relevant to your industry
- MITRE maintains profiles for 130+ APT groups
- Example: APT28 (Fancy Bear), APT29 (Cozy Bear), APT3

**Step 2: Map TTPs to Red Team Plan**
- Use MITRE ATT&CK Navigator (web-based visualization tool)
- Create layers mapping APT group TTPs to your operation
- Prioritize techniques based on detection coverage gaps

**Step 3: Emulate Adversary Behavior**
- Use tools like Atomic Red Team for pre-built technique tests
- Execute ATT&CK techniques in realistic sequence
- Document which techniques succeed vs. detected

**Step 4: Measure Detection Coverage**
- Mark techniques as: Detected (green), Partially Detected (yellow), Missed (red)
- Identify blind spots in security monitoring
- Provide heat map to blue team

## 7-Phase Cybersecurity Red Team Methodology

### Phase 1: Intelligence Gathering

**Objectives:**
- Build comprehensive target profile
- Identify attack surface
- Map technology stack

**OSINT Techniques:**
- **Domain Intelligence**: WHOIS, DNS records, subdomains (Sublist3r, Amass)
- **Network Intelligence**: Shodan, Censys for exposed services
- **Employee Intelligence**: LinkedIn, social media for org chart and technology mentions
- **Technology Stack**: Wappalyzer, BuiltWith, job postings
- **Document Metadata**: ExifTool for leaked files
- **Code Repositories**: GitHub for exposed credentials, architectural insights

**Active Reconnaissance:**
- **Network Scanning**: Nmap for port discovery and service enumeration
- **Web Application Mapping**: Burp Suite, OWASP ZAP
- **Email Harvesting**: theHarvester, Hunter.io

**Deliverable:** Intelligence report with target profile, asset inventory, initial attack vectors

### Phase 2: Vulnerability Detection

**Objectives:**
- Identify weaknesses in perimeter and internal systems
- Prioritize high-impact vulnerabilities
- Map vulnerabilities to ATT&CK techniques

**Technical Scanning:**
- **Network Vulnerability Scanners**: Nessus, OpenVAS, Qualys
- **Web Application Scanners**: Burp Suite Pro, Acunetix
- **Configuration Audits**: CIS benchmarks, security misconfigurations
- **Credential Stuffing**: Test for weak/default credentials

**Manual Testing:**
- **Authentication Bypass**: Logic flaws in login mechanisms
- **Injection Vulnerabilities**: SQL injection, command injection
- **Business Logic Flaws**: Privilege escalation, horizontal authorization bypass
- **API Security**: Broken object level authorization, excessive data exposure

**Vulnerability Prioritization:**
- **CVSS Score**: Technical severity rating
- **Exploitability**: Public exploits available? Exploit complexity?
- **Business Impact**: What data/systems are at risk?
- **Detection Likelihood**: Will exploit trigger alarms?

**Deliverable:** Prioritized vulnerability report with exploitation feasibility assessment

### Phase 3: Exploitation & Initial Access

**Objectives:**
- Gain foothold in target network
- Establish command & control (C2)
- Begin lateral movement preparation

**Common Initial Access Vectors:**

**External Exploitation:**
- **Web Application Exploits**: SQL injection, RCE, file upload vulnerabilities
- **VPN/RAS Exploits**: Pulse Secure, Citrix, Fortinet vulnerabilities
- **Email Server Exploits**: Exchange ProxyShell, ProxyLogon

**Phishing & Social Engineering:**
- **Spear Phishing**: Targeted emails with malicious attachments/links
- **Credential Harvesting**: Fake login pages to steal credentials
- **Watering Hole**: Compromise websites target employees visit
- **Pretexting**: Phone calls to helpdesk for password resets

**Physical Access:**
- **Badge Cloning**: RFID duplication for building access
- **Tailgating**: Follow authorized personnel into secure areas
- **USB Drops**: Plant USB Rubber Ducky with payload

**Exploitation Frameworks:**
- **Metasploit**: Comprehensive exploit database and post-exploitation
- **Cobalt Strike**: Commercial C2 platform (legitimate red team tool)
- **Empire/Starkiller**: PowerShell and Python post-exploitation framework
- **Sliver**: Open-source C2 framework

**Establishing C2:**
- **HTTP/HTTPS**: Blend with normal traffic
- **DNS Tunneling**: Exfiltrate data via DNS queries
- **Social Media**: Use Twitter/Telegram APIs for C2 (covert channel)
- **Cloud Services**: Dropbox, Google Drive for staging/exfiltration

**Deliverable:** Successful access to target network with C2 established

### Phase 4: Lateral Movement

**Objectives:**
- Move from initial foothold to high-value targets
- Avoid detection during network traversal
- Map internal network topology

**Lateral Movement Techniques:**

**Credential-Based Movement:**
- **Pass-the-Hash**: Authenticate using NTLM hash (Mimikatz)
- **Pass-the-Ticket**: Kerberos ticket reuse
- **Credential Dumping**: LSASS memory, SAM database, NTDS.dit
- **Kerberoasting**: Extract and crack service account hashes

**Exploitation-Based Movement:**
- **SMB Exploits**: EternalBlue (MS17-010), SMBGhost
- **RDP Exploits**: BlueKeep (CVE-2019-0708)
- **Remote Code Execution**: PSExec, WMI, WinRM

**Living-Off-the-Land (LOLBins):**
- **PowerShell**: Execute scripts in memory, avoid disk-based detection
- **WMI (Windows Management Instrumentation)**: Remote command execution
- **PsExec**: Sysinternals tool for lateral movement
- **DCOM**: Distributed COM for remote execution

**Internal Reconnaissance:**
- **Active Directory Enumeration**: BloodHound for AD attack paths
- **Network Scanning**: Discover additional hosts and services
- **Share Enumeration**: Find sensitive data in file shares
- **Service Account Discovery**: Identify high-privilege accounts

**Deliverable:** Network topology map with compromised hosts and privilege levels

### Phase 5: Privilege Escalation

**Objectives:**
- Gain administrative/root access
- Compromise domain administrator accounts
- Full control of critical infrastructure

**Windows Privilege Escalation:**
- **Unquoted Service Paths**: DLL hijacking in service paths
- **Weak Service Permissions**: Modify service binaries or configuration
- **Token Impersonation**: Rotten Potato, Juicy Potato
- **Kernel Exploits**: CVE-based privilege escalation
- **UAC Bypass**: User Account Control evasion

**Linux Privilege Escalation:**
- **SUID Binaries**: Exploit set-UID programs for root access
- **Kernel Exploits**: Dirty COW, local privilege escalation vulnerabilities
- **Sudo Misconfigurations**: NOPASSWD entries, wildcards
- **Cron Jobs**: Writable scripts executed as root
- **Capabilities**: Abuse Linux capabilities (e.g., CAP_SYS_ADMIN)

**Active Directory Attacks:**
- **Golden Ticket**: Forge Kerberos TGTs with KRBTGT hash
- **Silver Ticket**: Forge service tickets for specific services
- **DCSync**: Replicate Active Directory credentials
- **AdminSDHolder**: Persist via Protected Groups

**Deliverable:** Domain administrator or root-level access to critical systems

### Phase 6: Persistence

**Objectives:**
- Maintain access through reboots, credential changes
- Install multiple backdoors for redundancy
- Ensure covert long-term access

**Persistence Techniques:**

**Windows Persistence:**
- **Registry Run Keys**: HKLM/HKCU Run, RunOnce
- **Scheduled Tasks**: Trigger malicious payloads at intervals
- **Services**: Install malicious Windows services
- **WMI Event Subscriptions**: Execute code on WMI events
- **DLL Hijacking**: Replace legitimate DLLs with malicious versions

**Linux Persistence:**
- **SSH Keys**: Add public keys to `~/.ssh/authorized_keys`
- **Cron Jobs**: Schedule malicious scripts
- **Init Scripts**: `/etc/init.d/` or systemd services
- **LD_PRELOAD**: Inject shared libraries into processes
- **Web Shells**: Upload PHP/JSP shells to web servers

**Covert Persistence:**
- **Implants in Memory**: Fileless malware (e.g., Metasploit's Meterpreter)
- **Firmware Backdoors**: BIOS/UEFI rootkits
- **Supply Chain**: Compromise software update mechanisms

**Deliverable:** Multiple covert backdoors ensuring persistent access

### Phase 7: Objective Achievement & Reporting

**Objectives:**
- Accomplish defined red team goals (data exfiltration, system compromise)
- Document attack chain with evidence
- Provide comprehensive remediation guidance

**Common Objectives:**
- **Data Exfiltration**: Steal sensitive data (customer records, financial data, IP)
- **Ransomware Simulation**: Encrypt files (simulation only, no actual damage)
- **Domain Dominance**: Full control of Active Directory
- **Physical Control**: Gain access to secure facilities

**Evidence Collection:**
- **Screenshots**: Every critical step with timestamps
- **Log Files**: C2 logs, exploitation outputs
- **Proof of Compromise**: "Owned" files, screenshots of sensitive data access
- **Network Traffic Captures**: PCAP files showing attack traffic

**Reporting Structure:**
- **Executive Summary**
  - Business impact assessment
  - High-level risk scoring
  - Key findings (3-5 critical vulnerabilities)
  - Remediation timeline recommendations

- **Technical Findings**
  - Attack chain diagram (visual representation)
  - Detailed vulnerability descriptions
  - Exploitation procedures and evidence
  - MITRE ATT&CK mapping

- **Remediation Recommendations**
  - Prioritized by risk (Critical → High → Medium → Low)
  - Specific, actionable steps
  - Detection improvements for blue team
  - Long-term architectural recommendations

- **Indicators of Compromise (IOCs)**
  - IP addresses used
  - Tools and signatures
  - Techniques observable in logs
  - Blue team detection guidance

**Deliverable:** Comprehensive red team report with attack evidence and remediation roadmap

## Purple Team Practices

### What is Purple Teaming?

Purple teaming is collaborative security testing where red team (attackers) and blue team (defenders) work together openly, sharing knowledge in real-time to improve organizational security.

**Key Differences from Red Teaming:**
- **Red Team**: Covert, adversarial, blue team unaware
- **Purple Team**: Collaborative, blue team aware, knowledge sharing

### Purple Team Workflow

**Phase 1: Planning Together**
- Red and blue teams jointly define objectives
- Select ATT&CK techniques to test
- Blue team prepares detection hypotheses

**Phase 2: Controlled Attack Execution**
- Red team executes technique
- Blue team monitors detection systems in real-time
- Immediate feedback: "Did you see that?"

**Phase 3: Detection Tuning**
- If detected: Document successful detection
- If missed: Blue team creates new detection rule
- Re-test until reliable detection achieved

**Phase 4: Knowledge Transfer**
- Red team explains attacker tradecraft
- Blue team shares detection engineering insights
- Document lessons learned

### Purple Team Benefits
- Accelerates blue team skill development
- Validates detection coverage quickly
- Reduces time between detection gaps and fixes
- Fosters collaboration vs. adversarial culture

## Tools & Frameworks

### Command & Control (C2) Frameworks
- **Cobalt Strike**: Commercial, widely-used by red teams and APTs
- **Sliver**: Open-source, modern C2 with strong encryption
- **Empire**: PowerShell/Python post-exploitation framework
- **Mythic**: Collaborative C2 framework with web UI

### Adversary Emulation
- **Atomic Red Team**: Pre-built ATT&CK technique tests (Red Canary)
- **CALDERA**: Automated adversary emulation (MITRE)
- **APT Simulator**: Batch scripts simulating APT behavior

### Exploitation Frameworks
- **Metasploit**: Comprehensive exploit database
- **Covenant**: .NET C2 framework
- **Merlin**: Go-based post-exploitation platform

### Credential Attacks
- **Mimikatz**: Extract plaintext passwords, hashes, Kerberos tickets
- **BloodHound**: Active Directory attack path mapping
- **Rubeus**: Kerberos abuse toolkit
- **Impacket**: Python classes for network protocols

### Phishing & Social Engineering
- **Gophish**: Open-source phishing framework
- **Social-Engineer Toolkit (SET)**: Phishing, credential harvesting
- **EvilNginx**: Adversary-in-the-middle phishing framework

### OSINT & Reconnaissance
- **theHarvester**: Email, domain, subdomain enumeration
- **Shodan**: Search engine for internet-connected devices
- **Amass**: DNS enumeration and network mapping (OWASP)
- **SpiderFoot**: Automated OSINT collection

## Critical Reminders

- **MITRE ATT&CK is the Standard**: Map all activities to ATT&CK for interoperability
- **Stealth is Key**: Avoid detection to test realistic adversary scenarios
- **Document Everything**: Comprehensive evidence is essential for value delivery
- **Purple Team for Learning**: Collaborative exercises accelerate organizational improvement
- **Authorization First**: Never conduct red team activities without explicit written authorization
