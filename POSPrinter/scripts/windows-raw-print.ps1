param(
 [Parameter(Mandatory=$true)][string]$PrinterName,
 [Parameter(Mandatory=$true)][string]$DataBase64
)
$ErrorActionPreference='Stop'
$data=[Convert]::FromBase64String($DataBase64)
$source=@'
using System;
using System.Runtime.InteropServices;
public class PosRawPrinter {
 [StructLayout(LayoutKind.Sequential,CharSet=CharSet.Unicode)]
 public class DOC_INFO_1 { public string pDocName; public string pOutputFile; public string pDataType; }
 [DllImport("winspool.drv",SetLastError=true,CharSet=CharSet.Unicode)] public static extern bool OpenPrinter(string n,out IntPtr h,IntPtr d);
 [DllImport("winspool.drv",SetLastError=true)] public static extern bool ClosePrinter(IntPtr h);
 [DllImport("winspool.drv",SetLastError=true,CharSet=CharSet.Unicode)] public static extern int StartDocPrinter(IntPtr h,int l,[In] DOC_INFO_1 d);
 [DllImport("winspool.drv",SetLastError=true)] public static extern bool EndDocPrinter(IntPtr h);
 [DllImport("winspool.drv",SetLastError=true)] public static extern bool StartPagePrinter(IntPtr h);
 [DllImport("winspool.drv",SetLastError=true)] public static extern bool EndPagePrinter(IntPtr h);
 [DllImport("winspool.drv",SetLastError=true)] public static extern bool WritePrinter(IntPtr h,byte[] b,int c,out int w);
 public static void Send(string n,byte[] b){IntPtr h;if(!OpenPrinter(n,out h,IntPtr.Zero))throw new Exception("OpenPrinter failed "+Marshal.GetLastWin32Error());try{DOC_INFO_1 d=new DOC_INFO_1();d.pDocName="POS Receipt";d.pDataType="RAW";if(StartDocPrinter(h,1,d)==0)throw new Exception("StartDocPrinter failed "+Marshal.GetLastWin32Error());try{if(!StartPagePrinter(h))throw new Exception("StartPagePrinter failed "+Marshal.GetLastWin32Error());int w;if(!WritePrinter(h,b,b.Length,out w)||w!=b.Length)throw new Exception("WritePrinter failed "+Marshal.GetLastWin32Error());EndPagePrinter(h);}finally{EndDocPrinter(h);}}finally{ClosePrinter(h);}}
}
'@
Add-Type -TypeDefinition $source
[PosRawPrinter]::Send($PrinterName,$data)
