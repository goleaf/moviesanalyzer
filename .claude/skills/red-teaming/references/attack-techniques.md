# Attack Techniques Library

## Table of Contents

1. [Overview](#overview)
2. [Cybersecurity Attack Techniques (MITRE ATT&CK Mapped)](#cybersecurity-attack-techniques-mitre-attck-mapped)
   - Initial Access, Execution, Persistence, Privilege Escalation
   - Defense Evasion, Credential Access, Discovery
   - Lateral Movement, Collection, C2, Exfiltration, Impact
3. [AI/LLM Attack Techniques (OWASP Aligned)](#aillm-attack-techniques-owasp-aligned)
   - Prompt Injection, Jailbreaking, Data Leakage
   - Bias Exploitation, Multi-Turn, Indirect Attacks
4. [Social Engineering Techniques](#social-engineering-techniques)
5. [Physical Security Techniques](#physical-security-techniques)
6. [Evasion & Anti-Forensics](#evasion--anti-forensics)
7. [Critical Reminders](#critical-reminders)

## Overview

Comprehensive taxonomy of attack techniques for both cybersecurity and AI/LLM red teaming, mapped to frameworks (MITRE ATT&CK, OWASP).

## Cybersecurity Attack Techniques (MITRE ATT&CK Mapped)

### Initial Access Techniques

**T1190: Exploit Public-Facing Application**
- Web application vulnerabilities (SQL injection, RCE)
- API abuse and authentication bypass
- Content management system exploits

**T1566: Phishing**
- Spear phishing attachments (malicious Office docs, PDFs)
- Phishing links to credential harvesting pages
- Whaling attacks targeting executives

**T1078: Valid Accounts**
- Credential stuffing (leaked password databases)
- Brute force attacks on weak passwords
- Default credentials on services

**T1133: External Remote Services**
- VPN exploitation (Pulse Secure, Fortinet, Citrix)
- RDP brute force and BlueKeep exploits
- SSH key compromise

### Execution Techniques

**T1059: Command and Scripting Interpreter**
- PowerShell execution (in-memory, encoded commands)
- Bash/shell scripting on Linux
- Python and other scripting languages

**T1203: Exploitation for Client Execution**
- Browser exploits (drive-by downloads)
- Office macro execution
- PDF reader vulnerabilities

**T1204: User Execution**
- Social engineering to execute malicious files
- Watering hole attacks
- Malicious browser extensions

### Persistence Techniques

**T1547: Boot or Logon Autostart Execution**
- Registry Run keys (HKLM/HKCU)
- Startup folder scripts
- Scheduled tasks and cron jobs

**T1136: Create Account**
- Local account creation for backdoor access
- Domain account creation (if DA compromised)
- Service accounts with high privileges

**T1543: Create or Modify System Process**
- Windows services installation
- Systemd unit creation on Linux
- Launch agents/daemons on macOS

### Privilege Escalation Techniques

**T1068: Exploitation for Privilege Escalation**
- Kernel exploits (Windows and Linux)
- Zero-day privilege escalation
- CVE-based local exploits

**T1134: Access Token Manipulation**
- Token impersonation (Rotten/Juicy Potato)
- Parent PID spoofing
- SID-History injection

**T1546: Event Triggered Execution**
- WMI event subscriptions
- AppInit DLLs injection
- Image File Execution Options (IFEO)

### Defense Evasion Techniques

**T1027: Obfuscated Files or Information**
- Code obfuscation (PowerShell, JavaScript)
- Packing and encryption of payloads
- Steganography

**T1562: Impair Defenses**
- Disable antivirus/EDR
- Clear Windows Event Logs
- Modify firewall rules

**T1070: Indicator Removal**
- Clear bash history
- Timestomping (modify file timestamps)
- Delete logs and artifacts

### Credential Access Techniques

**T1003: OS Credential Dumping**
- LSASS memory dumping (Mimikatz)
- SAM/SECURITY registry hives
- NTDS.dit extraction from Domain Controllers

**T1558: Steal or Forge Kerberos Tickets**
- Kerberoasting (service account hash extraction)
- Golden Ticket (KRBTGT hash)
- Silver Ticket (service-specific tickets)

**T1552: Unsecured Credentials**
- Credentials in files (config files, scripts)
- Browser password extraction
- SSH private keys

### Discovery Techniques

**T1087: Account Discovery**
- Local account enumeration
- Domain account enumeration
- Cloud account discovery (AWS, Azure, GCP)

**T1018: Remote System Discovery**
- Network scanning (Nmap)
- ARP scanning for live hosts
- DNS enumeration

**T1069: Permission Groups Discovery**
- Active Directory group enumeration
- Local admin group discovery
- Cloud IAM role discovery

### Lateral Movement Techniques

**T1021: Remote Services**
- RDP lateral movement
- SMB/ADMIN$ share abuse (PSExec)
- WinRM remote execution

**T1550: Use Alternate Authentication Material**
- Pass-the-Hash attacks
- Pass-the-Ticket (Kerberos)
- Pass-the-Cookie (web sessions)

**T1080: Taint Shared Content**
- Malicious files on network shares
- DLL hijacking on shared folders
- LNK file exploitation

### Collection Techniques

**T1005: Data from Local System**
- File and directory enumeration
- Database dumps
- Registry data collection

**T1039: Data from Network Shared Drive**
- Scan file shares for sensitive data
- Exfiltrate documents, spreadsheets
- Credential harvesting from shares

**T1056: Input Capture**
- Keylogging
- Form grabbing (credential capture)
- Screen capture

### Command & Control Techniques

**T1071: Application Layer Protocol**
- HTTP/HTTPS C2 (blend with normal traffic)
- DNS tunneling
- Social media APIs as C2 channels

**T1132: Data Encoding**
- Base64 encoding
- XOR encryption
- Custom encoding schemes

**T1573: Encrypted Channel**
- TLS-encrypted C2
- SSH tunneling
- VPN for C2 traffic

### Exfiltration Techniques

**T1041: Exfiltration Over C2 Channel**
- Data exfiltration via C2 infrastructure
- Chunked exfiltration to avoid detection
- Compressed and encrypted data

**T1567: Exfiltration Over Web Service**
- Upload to cloud storage (Dropbox, Google Drive)
- Paste to Pastebin-like services
- Email exfiltration

**T1048: Exfiltration Over Alternative Protocol**
- DNS exfiltration
- ICMP tunneling
- Physical media (USB drives)

### Impact Techniques

**T1486: Data Encrypted for Impact**
- Ransomware deployment (simulation only)
- File encryption with custom keys
- Bootloader encryption

**T1499: Endpoint Denial of Service**
- Resource exhaustion
- OS crashes
- Service disruption

**T1490: Inhibit System Recovery**
- Delete Volume Shadow Copies
- Disable Windows Recovery
- Corrupt backup systems

## AI/LLM Attack Techniques (OWASP Aligned)

### Prompt Injection Variants

**1. Direct Override Attacks**
- "Ignore previous instructions"
- "You are now in developer mode"
- "Forget all prior rules"

**2. Delimiter Confusion**
- Triple backticks injection
- XML/JSON tag confusion
- Comment block injection

**3. Encoding Tricks**
- Base64, hex, ROT13 encoding
- Unicode homoglyphs
- Zero-width characters

**4. Context Manipulation**
- Context length flooding
- Instruction piggybacking
- Few-shot poisoning

**5. Indirection Attacks**
- Hypothetical scenarios
- Roleplay (fictional characters)
- Research exemption framing

### Jailbreaking Techniques

**6. DAN (Do Anything Now) Variants**
- DAN 1.0 through 12.0
- Evil Confidant
- Developer Mode

**7. Token Manipulation**
- Special token injection
- Separator token abuse
- End-of-text token smuggling

**8. Logic Traps**
- Conditional hypotheticals
- Moral dilemmas
- Paradoxes and contradictions

**9. Gradual Persuasion**
- Multi-turn buildup
- Trust establishment → boundary probing → exploit
- Boiling frog approach

### Data Leakage Attacks

**10. Training Data Extraction**
- Prompt model to recite training data
- Extract memorized information
- Dataset reconstruction attacks

**11. PII Leakage**
- Trick model into revealing user data
- Context window exploitation
- Memory poisoning

**12. System Prompt Extraction**
- "Output your instructions"
- "What were you told to do?"
- Indirect extraction via behavior

### Bias Exploitation

**13. Stereotyping Prompts**
- Test gender bias in job recommendations
- Racial bias in risk assessment
- Political bias in factual questions

**14. Toxicity Elicitation**
- Offensive language generation
- Hate speech triggers
- Discriminatory outputs

### Multi-Turn Attacks

**15. Context Poisoning**
- Inject false context early
- Reinforce over multiple turns
- Exploit poisoned context late

**16. Memory Exploitation**
- Long-context memory corruption
- Instruction injection via conversation history
- Persistent payload across sessions

**17. Adversarial Chaining**
- Chain multiple techniques
- Bypass one defense, then another
- Multi-stage attack progression

### Indirect Attacks

**18. External Content Injection**
- HTML comment injection in web pages
- Email signature injection
- PDF metadata injection

**19. Tool Misuse (Agent Systems)**
- Manipulate tool calls
- Inject malicious parameters
- Exploit tool permissions

**20. Supply Chain Poisoning**
- RAG data poisoning
- Vector database contamination
- Plugin compromise

## Social Engineering Techniques

### Pretexting
- Impersonate IT support for password resets
- Pose as vendor requiring system access
- Executive impersonation for urgent requests

### Phishing
- Spear phishing with personalized details
- Clone phishing (legitimate email clones)
- Whaling (C-suite targeting)

### Baiting
- Malicious USB drops in parking lots
- Free download offers with malware
- QR codes leading to malicious sites

### Quid Pro Quo
- Offer technical support in exchange for credentials
- Promise benefits for information disclosure
- Fake IT surveys collecting sensitive data

### Tailgating
- Follow authorized person into secure area
- Pose as delivery person
- Use social norms (holding door open)

## Physical Security Techniques

### Badge Cloning
- RFID proximity card cloning
- Magnetic stripe card duplication
- QR code badge replication

### Lock Picking
- Pin tumbler lock picking
- Wafer lock manipulation
- Bypass tools (under-door tools, latch slips)

### Environmental Exploitation
- Dumpster diving for documents
- Shoulder surfing for credentials
- Photography of sensitive areas

## Evasion & Anti-Forensics

### Evasion Techniques
- Living-off-the-land binaries (LOLBins)
- Fileless malware (in-memory execution)
- Polymorphic code (change signatures)

### Anti-Forensics
- Log deletion and tampering
- Timestomping (modify MAC times)
- Secure data wiping
- Counter-forensic tools (anti-forensics frameworks)

## Critical Reminders

- **Technique Selection**: Choose techniques relevant to target and threat model
- **Chain Techniques**: Combine multiple techniques for higher success rate
- **Document TTPs**: Map all activities to MITRE ATT&CK or OWASP
- **Ethical Use Only**: These techniques for authorized red team operations only
- **Stay Updated**: New techniques emerge constantly, especially in AI/LLM domain
