import { useState, useEffect, useCallback, useRef } from "react";
import { SendQuestionnaireModal, ResponseViewer, EmailSettings } from "./EmailAndResponses";
import QuestionnaireManager from "./QuestionnaireManager";
import JobSelector from "./JobSelector";

const IS_ELECTRON = typeof window !== "undefined" && !!window.electronAPI?.isElectron;
const API = "http://localhost:5001";

/*    helpers    */
async function apiFetch(method, url, body) {
  const isForm = body instanceof FormData;
  const res = await fetch(`${API}${url}`, {
    method,
    headers: (!isForm && body) ? { "Content-Type": "application/json" } : {},
    body: body ? (isForm ? body : JSON.stringify(body)) : null,
  });
  return res.json();
}

/*    colours    */
const K = {
  bg:"#080C18", surf:"#0D1225", card:"#111830", bdr:"#1A2440",
  acc:"#00D4FF", accD:"#0090B3", gold:"#FFB800",
  grn:"#00E87A", red:"#FF4D6A", org:"#FF8C42", pur:"#A855F7",
  txt:"#E8EDF8", mut:"#5A6A90", dim:"#1E2A48",
};

const ST = {
  Shortlisted:{ c:K.grn, bg:"#00E87A12", bd:"#00E87A35" },
  Review:     { c:K.org, bg:"#FF8C4212", bd:"#FF8C4235" },
  Rejected:   { c:K.red, bg:"#FF4D6A12", bd:"#FF4D6A35" },
};
const RC = {
  "Strong Hire":{ c:K.grn, i:"  " },
  "Hire":       { c:K.acc, i:"  "  },
  "Maybe":      { c:K.org, i:"~"  },
  "Pass":       { c:K.red, i:"  "  },
  "Pending":    { c:K.mut, i:"  "  },
};

const box = (x={}) => ({
  width:"100%", background:K.surf, border:`1px solid ${K.bdr}`,
  borderRadius:8, padding:"9px 12px", color:K.txt, fontSize:13,
  outline:"none", fontFamily:"inherit", boxSizing:"border-box", ...x,
});

/*    tiny components    */
const Spin = ({s=28,c=K.acc}) => (
  <div style={{width:s,height:s,border:`3px solid ${K.dim}`,borderTopColor:c,
    borderRadius:"50%",animation:"spin .8s linear infinite",flexShrink:0}}/>
);

const Chip = ({label,color,bg,bd,sm}) => (
  <span style={{background:bg||`${color}18`,border:`1px solid ${bd||color+"40"}`,
    borderRadius:6,padding:sm?"1px 7px":"3px 10px",fontSize:sm?10:11,
    color,fontWeight:700,whiteSpace:"nowrap"}}>{label}</span>
);

const Bar = ({v,c}) => (
  <div style={{display:"flex",alignItems:"center",gap:8}}>
    <div style={{flex:1,height:5,background:K.dim,borderRadius:3,overflow:"hidden"}}>
      <div style={{height:"100%",width:`${Math.min(100,v||0)}%`,background:c,borderRadius:3,
        boxShadow:`0 0 6px ${c}80`}}/>
    </div>
    <span style={{color:c,fontSize:11,fontWeight:700,minWidth:24,fontFamily:"monospace",textAlign:"right"}}>
      {Math.round(v||0)}
    </span>
  </div>
);

const Ring = ({score,size=68}) => {
  const c = score>=80?K.grn:score>=60?K.acc:score>=40?K.org:K.red;
  const r=(size-8)/2, circ=2*Math.PI*r, off=circ*(1-(score||0)/100);
  return (
    <svg width={size} height={size}>
      <circle cx={size/2} cy={size/2} r={r} fill="none" stroke={K.dim} strokeWidth={5}/>
      <circle cx={size/2} cy={size/2} r={r} fill="none" stroke={c} strokeWidth={5}
        strokeDasharray={circ} strokeDashoffset={off} strokeLinecap="round"
        style={{transformOrigin:"center",transform:"rotate(-90deg)",filter:`drop-shadow(0 0 5px ${c})`}}/>
      <text x={size/2} y={size/2} textAnchor="middle" dominantBaseline="central"
        fill={c} fontSize={size*.22} fontWeight="800" fontFamily="monospace">
        {Math.round(score||0)}
      </text>
    </svg>
  );
};

/*    API Key modal    */
function KeyModal({onSave}) {
  const [v,setV] = useState("");
  const ok = v.startsWith("sk-");
  return (
    <div style={{position:"fixed",inset:0,background:"#000D",display:"flex",
      alignItems:"center",justifyContent:"center",zIndex:2000}}>
      <div style={{background:K.card,border:`1px solid ${K.bdr}`,borderRadius:20,
        padding:40,maxWidth:440,width:"90%"}}>
        <div style={{fontSize:34,textAlign:"center",marginBottom:10}}>  </div>
        <div style={{fontSize:20,fontWeight:800,textAlign:"center",color:K.txt,marginBottom:6}}>
          Anthropic API Key
        </div>
        <div style={{fontSize:12,color:K.mut,textAlign:"center",lineHeight:1.7,marginBottom:22}}>
          Get your key at{" "}
          <span style={{color:K.acc}}>console.anthropic.com</span>
          <br/>Stored in memory only   never saved to disk.
        </div>
        <input type="password" value={v} onChange={e=>setV(e.target.value)}
          onKeyDown={e=>e.key==="Enter"&&ok&&onSave(v)}
          placeholder="sk-ant-api03-..."
          style={box({marginBottom:14,letterSpacing:.8})}/>
        <button onClick={()=>onSave(v)} disabled={!ok} style={{
          width:"100%",border:"none",borderRadius:10,
          background:ok?`linear-gradient(135deg,${K.acc},${K.accD})`:K.dim,
          color:ok?K.bg:K.mut,padding:"13px",cursor:ok?"pointer":"not-allowed",
          fontSize:14,fontWeight:800,transition:"all .2s"}}>
          Continue  
        </button>
      </div>
    </div>
  );
}

/*    Upload zone    */
function DropZone({onFiles,busy}) {
  const [drag,setDrag] = useState(false);
  const ref = useRef();
  const ok  = f => /\.(pdf|docx|doc)$/i.test(f.name);

  return (
    <div onDragOver={e=>{e.preventDefault();setDrag(true)}}
      onDragLeave={()=>setDrag(false)}
      onDrop={e=>{e.preventDefault();setDrag(false);
        const fs=[...e.dataTransfer.files].filter(ok);
        if(fs.length)onFiles(fs);}}
      onClick={()=>!busy&&ref.current.click()}
      style={{border:`2px dashed ${drag?K.acc:K.bdr}`,borderRadius:16,
        padding:"44px 24px",textAlign:"center",
        background:drag?`${K.acc}08`:K.surf,
        cursor:busy?"default":"pointer",transition:"all .2s",
        boxShadow:drag?`0 0 28px ${K.acc}20`:"none"}}>
      <input ref={ref} type="file" multiple accept=".pdf,.docx,.doc"
        onChange={e=>{
          const fs=[...e.target.files].filter(ok);
          if(fs.length)onFiles(fs);
          e.target.value="";
        }} style={{display:"none"}}/>
      {busy ? (
        <div style={{display:"flex",flexDirection:"column",alignItems:"center",gap:14}}>
          <Spin s={42}/>
          <div style={{fontSize:14,fontWeight:700,color:K.acc}}>Uploading &amp; parsing resumes  </div>
          <div style={{fontSize:12,color:K.mut}}>AI is extracting candidate information</div>
        </div>
      ) : (
        <>
          <div style={{fontSize:46,marginBottom:12}}>{drag?"   ":"  "}</div>
          <div style={{fontSize:15,fontWeight:700,color:K.txt,marginBottom:8}}>
            {drag?"Release to upload!":"Drag & drop resumes here"}
          </div>
          <div style={{fontSize:12,color:K.mut,marginBottom:20}}>
            PDF and DOCX supported    Multiple files at once
          </div>
          <div style={{display:"inline-block",background:`${K.acc}18`,
            border:`1px solid ${K.acc}45`,borderRadius:8,
            padding:"9px 24px",color:K.acc,fontSize:13,fontWeight:700}}>
               📂 Browse Files
          </div>
        </>
      )}
    </div>
  );
}

/*    Candidate detail modal    */
function DetailModal({c,onClose,onStatus,onDelete}) {
  if(!c) return null;
  const rc  = RC[c.recommendation]||RC["Pending"];
  const sc  = ST[c.status]||ST["Review"];
  const scr = c.overall_score>=80?K.grn:c.overall_score>=60?K.acc:c.overall_score>=40?K.org:K.red;

  return (
    <div style={{position:"fixed",inset:0,background:"#000C",display:"flex",
      alignItems:"center",justifyContent:"center",zIndex:1500,padding:16}}
      onClick={e=>e.target===e.currentTarget&&onClose()}>
      <div style={{background:K.card,border:`1px solid ${K.bdr}`,borderRadius:20,
        padding:28,maxWidth:700,width:"100%",maxHeight:"90vh",overflowY:"auto"}}>

        {/* header */}
        <div style={{display:"flex",justifyContent:"space-between",alignItems:"flex-start",marginBottom:22}}>
          <div>
            <div style={{fontSize:21,fontWeight:800,color:K.txt}}>{c.name}</div>
            <div style={{fontSize:12,color:K.mut,marginTop:3,display:"flex",gap:12,flexWrap:"wrap"}}>
              {c.email&&<span>   {c.email}</span>}
              {c.phone&&<span>    {c.phone}</span>}
            </div>
          </div>
          <button onClick={onClose} style={{background:`${K.red}18`,border:`1px solid ${K.red}35`,
            borderRadius:8,color:K.red,cursor:"pointer",fontSize:16,padding:"5px 11px",fontWeight:700}}>  </button>
        </div>

        {/* score */}
        {c.screened===1&&(
          <div style={{display:"grid",gridTemplateColumns:"auto 1fr",gap:20,
            background:K.surf,borderRadius:14,padding:20,marginBottom:18}}>
            <Ring score={c.overall_score} size={84}/>
            <div>
              <div style={{display:"flex",gap:8,flexWrap:"wrap",marginBottom:10}}>
                {c.rank>0&&<Chip label={`   Rank #${c.rank}`} color={K.gold}/>}
                <Chip label={`${rc.i} ${c.recommendation}`} color={rc.c}/>
                <Chip label={c.status} color={sc.c} bg={sc.bg} bd={sc.bd}/>
              </div>
              <div style={{fontSize:12,color:K.mut,lineHeight:1.65}}>{c.summary}</div>
            </div>
          </div>
        )}

        {/* sub-scores */}
        {c.screened===1&&(
          <div style={{display:"grid",gridTemplateColumns:"1fr 1fr 1fr",gap:10,marginBottom:18}}>
            {[["    Skills",c.skills_match],["    Experience",c.exp_match],["    Education",c.edu_match]].map(([l,v])=>(
              <div key={l} style={{background:K.surf,borderRadius:10,padding:"10px 14px"}}>
                <div style={{fontSize:10,color:K.mut,textTransform:"uppercase",letterSpacing:.8,marginBottom:7}}>{l}</div>
                <Bar v={v||0} c={(v||0)>=70?K.grn:(v||0)>=45?K.acc:K.org}/>
              </div>
            ))}
          </div>
        )}

        {/* strengths & gaps */}
        {c.screened===1&&(c.strengths?.length>0||c.gaps?.length>0)&&(
          <div style={{display:"grid",gridTemplateColumns:"1fr 1fr",gap:12,marginBottom:18}}>
            <div style={{background:`${K.grn}0A`,border:`1px solid ${K.grn}25`,borderRadius:12,padding:14}}>
              <div style={{fontSize:10,color:K.grn,fontWeight:700,letterSpacing:1,textTransform:"uppercase",marginBottom:10}}>   ✔ ✔ Strengths</div>
              {(c.strengths||[]).map((s,i)=>(
                <div key={i} style={{display:"flex",gap:7,marginBottom:6,fontSize:12,color:K.txt}}>
                  <span style={{color:K.grn,flexShrink:0}}> </span>{s}
                </div>
              ))}
            </div>
            <div style={{background:`${K.org}0A`,border:`1px solid ${K.org}25`,borderRadius:12,padding:14}}>
              <div style={{fontSize:10,color:K.org,fontWeight:700,letterSpacing:1,textTransform:"uppercase",marginBottom:10}}>   △ △ Gaps</div>
              {(c.gaps||[]).map((g,i)=>(
                <div key={i} style={{display:"flex",gap:7,marginBottom:6,fontSize:12,color:K.txt}}>
                  <span style={{color:K.org,flexShrink:0}}> </span>{g}
                </div>
              ))}
            </div>
          </div>
        )}

        {/* info */}
        <div style={{display:"grid",gridTemplateColumns:"1fr 1fr",gap:10,marginBottom:18}}>
          <div style={{background:K.surf,borderRadius:10,padding:14}}>
            <div style={{fontSize:10,color:K.mut,textTransform:"uppercase",letterSpacing:.8,marginBottom:7}}>    Education</div>
            <div style={{fontSize:13,color:K.txt}}>{c.education||" "}</div>
          </div>
          <div style={{background:K.surf,borderRadius:10,padding:14}}>
            <div style={{fontSize:10,color:K.mut,textTransform:"uppercase",letterSpacing:.8,marginBottom:7}}>    Experience</div>
            <div style={{fontSize:13,color:K.txt}}>{c.experience||" "}</div>
          </div>
        </div>

        {/* skills */}
        {(c.skills||[]).length>0&&(
          <div style={{marginBottom:20}}>
            <div style={{fontSize:10,color:K.mut,textTransform:"uppercase",letterSpacing:.8,marginBottom:8}}>    Skills</div>
            <div style={{display:"flex",flexWrap:"wrap",gap:6}}>
              {c.skills.map((s,i)=>(
                <span key={i} style={{background:K.dim,borderRadius:6,padding:"3px 10px",
                  fontSize:11,color:K.txt,border:`1px solid ${K.bdr}`}}>{s}</span>
              ))}
            </div>
          </div>
        )}

        {/* actions */}
        <div style={{display:"flex",gap:10,flexWrap:"wrap",borderTop:`1px solid ${K.bdr}`,paddingTop:18}}>
          {["Shortlisted","Review","Rejected"].map(s=>{
            const sc2 = ST[s];
            const act = c.status===s;
            return (
              <button key={s} onClick={()=>onStatus(c.id,s)} style={{
                background:act?sc2.bg:K.surf, border:`1px solid ${act?sc2.c:K.bdr}`,
                borderRadius:8,color:act?sc2.c:K.mut,padding:"8px 16px",
                cursor:"pointer",fontSize:12,fontWeight:700,transition:"all .2s"}}>
                {s}
              </button>
            );
          })}
          <div style={{flex:1}}/>
          <button onClick={()=>{
            if(IS_ELECTRON) window.electronAPI.openResume(c.id);
            else window.open(`${API}/candidate/${c.id}/file`,"_blank");
          }} style={{background:`${K.acc}15`,border:`1px solid ${K.acc}40`,
            borderRadius:8,color:K.acc,padding:"8px 16px",cursor:"pointer",fontSize:12,fontWeight:700}}>
               View Resume
          </button>
          <button onClick={()=>{onDelete(c.id);onClose();}} style={{
            background:`${K.red}12`,border:`1px solid ${K.red}35`,
            borderRadius:8,color:K.red,padding:"8px 14px",cursor:"pointer",fontSize:12,fontWeight:700}}>
               Delete
          </button>
        </div>
      </div>
    </div>
  );
}

/*                                                       
   MAIN APP
                                                       */
export default function App() {
  const [apiKey, setApiKey] = useState("");
  const [showKey, setShowKey] = useState(false);
  const [tab,     setTab]     = useState("upload");
  const [jobTitle,setJobTitle]= useState("");
  const [jobId,   setJobId]   = useState("");
  const [jobDesc, setJobDesc] = useState(

`Requirements:
- 5+ years Python development
- FastAPI or Django REST Framework
- PostgreSQL, Redis, Kafka/RabbitMQ
- AWS or GCP, Docker, Kubernetes
- CI/CD pipelines

Responsibilities:
- Design scalable backend services
- Lead architecture decisions
- Mentor junior developers`);

  const [selectedJob, setSelectedJob] = useState(null);
  const [jobs, setJobs] = useState([]);
  const [candidates,setCandidates] = useState([]);
  const [stats,     setStats]      = useState(null);
  const [selected,  setSelected]   = useState(null);
  const [sendModal, setSendModal] = useState(null);
  const [viewResp, setViewResp]   = useState(null);
  const [uploading, setUploading]  = useState(false);
  const [screening, setScreening]  = useState(false);
  const [queue,     setQueue]      = useState([]);
  const [search,    setSearch]     = useState("");
  const [fSt,       setFSt]        = useState("");
  const [fRec,      setFRec]       = useState("");
  const [error,     setError]      = useState("");
  const [toast,     setToast]      = useState("");

  const flash = msg => { setToast(msg); setTimeout(()=>setToast(""), 3000); };

  const reload = useCallback(async () => {
    try {
      const q  = {};
      if(search) q.search = search;
      if(fSt)    q.status = fSt;
      if(fRec)   q.recommendation = fRec;
      const qs = Object.keys(q).length ? "?"+new URLSearchParams(q) : "";

      // Always fetch jobs list
      const jb = await apiFetch("GET","/jobs");
      setJobs(jb.jobs||[]);

      if(selectedJob) {
        // Fetch candidates and stats for selected job
        const [cd, st] = await Promise.all([
          apiFetch("GET",`/jobs/${selectedJob.id}/candidates${qs}`),
          apiFetch("GET",`/jobs/${selectedJob.id}/stats`),
        ]);
        const ranked1 = (cd.candidates||[])
          .sort((a,b)=>(b.overall_score||0)-(a.overall_score||0))
          .map((c,i)=>({...c, rank: i+1}));
        setCandidates(ranked1);
        setStats({
          total:       st.total       || 0,
          screened:    st.screened    || 0,
          shortlisted: st.shortlisted || 0,
          avg_score:   st.avg_score   || 0,
          strong_hire: st.strong_hire || 0,
        });
      } else {
        // No job selected - fetch all candidates and global stats
        const [cd, st] = await Promise.all([
          apiFetch("GET",`/candidates${qs}`),
          apiFetch("GET","/stats"),
        ]);
        const ranked2 = (cd.candidates||[])
          .sort((a,b)=>(b.overall_score||0)-(a.overall_score||0))
          .map((c,i)=>({...c, rank: i+1}));
        setCandidates(ranked2);
        setStats(st);
      }
    } catch(e){ console.error(e); }
  },[search,fSt,fRec,selectedJob]);

  useEffect(()=>{ if(!showKey) reload(); },[showKey]);
  useEffect(()=>{ if(!showKey) reload(); },[search,fSt,fRec,selectedJob]);

  /* save key */
  const handleKey = async k => {
    setApiKey(k);
    if(IS_ELECTRON) await window.electronAPI.setApiKey(k);
    setShowKey(false);
    reload();
  };

  /* upload */
  const handleFiles = async files => {
    if(!jobId) {
      setError("Please select a Job Posting before uploading resumes.");
      return;
    }
    setUploading(true); setError("");
    setQueue(files.map(f=>({name:f.name,done:false})));
    try {
      let res;
      if(IS_ELECTRON) {
        res = await window.electronAPI.uploadFiles({
          filePaths: files.map(f=>f.path), apiKey, jobTitle, jobDesc });
      } else {
        const form = new FormData();
        form.append("api_key",  apiKey);
        form.append("job_title",jobTitle);
        form.append("job_desc", jobDesc);
        form.append("job_id", jobId);
        files.forEach(f=>form.append("resumes",f));
        res = await apiFetch("POST","/upload",form);
      }
      if(res.error) throw new Error(res.error);
      setQueue(files.map(f=>({name:f.name,done:true})));
      flash(`   ${res.count} resume${res.count!==1?"s":""} uploaded and parsed!`);
      await reload();
      setTimeout(()=>setTab("candidates"),800);
    } catch(e) { setError(e.message); setQueue([]); }
    finally    { setUploading(false); }
  };

  /* screen */
  const handleScreen = async () => {
    setScreening(true); setError("");
    try {
      const body = { api_key:apiKey, job_title:jobTitle, job_desc:jobDesc };
      const res  = IS_ELECTRON
        ? await window.electronAPI.screenAll(body)
        : await apiFetch("POST","/screen-all",body);
      if(res.error) throw new Error(res.error);
      flash(`   ${res.count} candidate${res.count!==1?"s":""} screened and ranked!`);
      await reload();
    } catch(e) { setError(e.message); }
    finally    { setScreening(false); }
  };

  const handleStatus = async (id,status) => {
    if(IS_ELECTRON) await window.electronAPI.updateStatus(id,status);
    else            await apiFetch("PATCH",`/candidate/${id}/status`,{status});
    await reload();
    if(selected?.id===id) setSelected(p=>({...p,status}));
  };

  const handleDelete = async id => {
    if(IS_ELECTRON) await window.electronAPI.deleteCandidate(id);
    else            await apiFetch("DELETE",`/candidate/${id}`);
    await reload();
  };

  const unscreened = candidates.filter(c=>c.screened===0).length;

  /*    render    */
  return (
    <div style={{minHeight:"100vh",background:K.bg,color:K.txt,
      fontFamily:"'Segoe UI',system-ui,sans-serif"}}>
      <style>{`
        @keyframes spin{to{transform:rotate(360deg)}}
        input::placeholder,textarea::placeholder{color:${K.mut}66}
        select option{background:${K.card};color:${K.txt}}
        *{box-sizing:border-box}
      `}</style>

      {showKey  && <KeyModal onSave={handleKey}/>}
      {selected && <DetailModal c={selected} onClose={()=>setSelected(null)}
        onStatus={handleStatus} onDelete={handleDelete}/>}

      {/* toast */}
      {toast&&(
        <div style={{position:"fixed",top:20,right:20,zIndex:3000,
          background:`${K.grn}18`,border:`1px solid ${K.grn}50`,
          borderRadius:10,padding:"10px 18px",color:K.grn,
          fontSize:13,fontWeight:700,boxShadow:"0 8px 24px #0006"}}>
          {toast}
        </div>
      )}

      {/*    HEADER    */}
      <header style={{background:K.surf,borderBottom:`1px solid ${K.bdr}`,
        padding:"0 28px",position:"sticky",top:0,zIndex:100}}>
        <div style={{maxWidth:1360,margin:"0 auto",height:60,
          display:"flex",alignItems:"center",gap:16}}>

          <div style={{display:"flex",alignItems:"center",gap:10,flexShrink:0}}>
            <div style={{width:36,height:36,background:`${K.acc}18`,
              border:`2px solid ${K.acc}45`,borderRadius:9,
              display:"flex",alignItems:"center",justifyContent:"center",fontSize:18}}>   </div>
            <div>
              <div style={{fontSize:15,fontWeight:800,lineHeight:1}}>AI Resume Screener</div>
              <div style={{fontSize:9,color:K.mut,letterSpacing:.8}}>POWERED BY CLAUDE</div>
            </div>
          </div>

          <nav style={{display:"flex",gap:4,marginLeft:24}}>
            {[["upload","📤 Upload"],["candidates","👥 Candidates"],["questionnaires","📋 Questionnaires"],["email","📧 Email Settings"]].map(([t,l])=>(
              <button key={t} onClick={()=>setTab(t)} style={{
                background:tab===t?`${K.acc}15`:"none",
                border:`1px solid ${tab===t?K.acc:K.bdr}`,
                borderRadius:8,color:tab===t?K.acc:K.mut,
                padding:"6px 16px",cursor:"pointer",fontSize:13,
                fontWeight:tab===t?700:400,transition:"all .15s"}}>
                {l}
              </button>
            ))}
          </nav>

          <div style={{marginLeft:"auto",display:"flex",gap:8,alignItems:"center"}}>
            {stats&&[
              {l:"📋 Total",    v:stats.total,       c:K.acc},
              {l:"🔍 Screened", v:stats.screened,    c:K.pur},
              {l:"✅ Shortlist",v:stats.shortlisted, c:K.grn},
              {l:"🌟 Avg Score",v:stats.avg_score,   c:K.gold},
            ].map(({l,v,c})=>(
              <div key={l} style={{background:`${c}10`,border:`1px solid ${c}30`,
                borderRadius:8,padding:"4px 12px",textAlign:"center",minWidth:56}}>
                <div style={{fontSize:16,fontWeight:800,color:c,fontFamily:"monospace",lineHeight:1}}>{v}</div>
                <div style={{fontSize:9,color:K.mut,textTransform:"uppercase",letterSpacing:.7,marginTop:1}}>{l}</div>
              </div>
            ))}
            <button onClick={()=>setShowKey(true)} style={{
              background:`${K.acc}10`,border:`1px solid ${K.acc}30`,borderRadius:7,
              color:K.acc,fontSize:11,padding:"6px 12px",cursor:"pointer",fontWeight:600}}>🔑 Key</button>
          </div>
        </div>
      </header>

      <main style={{maxWidth:1360,margin:"0 auto",padding:"24px 20px"}}>

        {/* error */}
        {error&&(
          <div style={{background:`${K.red}10`,border:`1px solid ${K.red}35`,
            borderRadius:12,padding:"11px 18px",marginBottom:20,
            display:"flex",justifyContent:"space-between",alignItems:"center"}}>
            <span style={{color:K.red,fontSize:13}}>    {error}</span>
            <button onClick={()=>setError("")} style={{background:"none",border:"none",
              color:K.red,cursor:"pointer",fontSize:18,lineHeight:1}}>  </button>
          </div>
        )}

        {/*              UPLOAD TAB              */}
        {tab==="upload"&&(
          <div style={{display:"grid",gridTemplateColumns:"1fr 460px",gap:24,alignItems:"start"}}>

            <div style={{display:"flex",flexDirection:"column",gap:20}}>
              <div style={{background:K.card,border:`1px solid ${K.bdr}`,borderRadius:16,padding:24}}>
                <div style={{fontSize:11,color:K.acc,fontWeight:700,letterSpacing:1.5,
                  textTransform:"uppercase",marginBottom:16}}>    📤 Upload Resumes</div>
                <DropZone onFiles={handleFiles} busy={uploading}/>

                {queue.length>0&&(
                  <div style={{marginTop:16,display:"flex",flexDirection:"column",gap:7}}>
                    {queue.map((f,i)=>(
                      <div key={i} style={{display:"flex",alignItems:"center",gap:10,
                        background:K.surf,borderRadius:9,padding:"9px 14px",
                        border:`1px solid ${f.done?K.grn+"40":K.bdr}`}}>
                        <span style={{fontSize:18}}>{f.name.endsWith(".pdf")?"  ":"  "}</span>
                        <span style={{flex:1,fontSize:12,color:K.txt}}>{f.name}</span>
                        {f.done
                          ?<span style={{fontSize:11,color:K.grn,fontWeight:700}}>   Parsed</span>
                          :<div style={{display:"flex",alignItems:"center",gap:6}}>
                            <Spin s={14}/><span style={{fontSize:11,color:K.acc}}>Processing  </span>
                          </div>}
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {unscreened>0&&(
                <button onClick={handleScreen} disabled={screening} style={{
                  background:screening?K.dim:`linear-gradient(135deg,${K.acc},${K.accD})`,
                  border:"none",borderRadius:14,color:screening?K.mut:K.bg,
                  padding:"16px",cursor:screening?"not-allowed":"pointer",
                  fontSize:15,fontWeight:800,display:"flex",alignItems:"center",
                  justifyContent:"center",gap:12,
                  boxShadow:screening?"none":`0 6px 28px ${K.acc}30`,transition:"all .3s"}}>
                  {screening
                    ? <><Spin s={22} c={K.mut}/>AI screening in progress  </>
                    : `    Screen ${unscreened} Candidate${unscreened!==1?"s":""} with AI`}
                </button>
              )}

              {stats?.total>0&&(
                <div style={{display:"grid",gridTemplateColumns:"repeat(4,1fr)",gap:12}}>
                  {[
                    {icon:"  ",label:"📋 Total",       value:stats.total,       color:K.acc},
                    {icon:"  ",label:"🔍 Screened",    value:stats.screened,    color:K.pur},
                    {icon:"  ",label:"✅ Shortlisted", value:stats.shortlisted, color:K.grn},
                    {icon:"  ",label:"⭐ Strong Hire", value:stats.strong_hire, color:K.gold},
                  ].map(({icon,label,value,color})=>(
                    <div key={label} style={{background:K.card,border:`1px solid ${K.bdr}`,
                      borderRadius:14,padding:"16px 18px",display:"flex",alignItems:"center",gap:12}}>
                      <div style={{width:40,height:40,background:`${color}15`,
                        border:`1px solid ${color}35`,borderRadius:10,
                        display:"flex",alignItems:"center",justifyContent:"center",fontSize:18}}>{icon}</div>
                      <div>
                        <div style={{fontSize:24,fontWeight:800,color,fontFamily:"monospace",lineHeight:1}}>{value}</div>
                        <div style={{fontSize:10,color:K.mut,textTransform:"uppercase",letterSpacing:.8,marginTop:2}}>{label}</div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* job panel */}
            <div style={{display:"flex",flexDirection:"column",gap:14}}>
              <div style={{background:K.card,border:`1px solid ${K.bdr}`,borderRadius:16,padding:22}}>
                <div style={{fontSize:11,color:K.acc,fontWeight:700,letterSpacing:1.5,
                  textTransform:"uppercase",marginBottom:16}}>📁 Job Details</div>
                <JobSelector onJobChange={({jobTitle:t, jobDesc:d, jobId:id})=>{
                setJobTitle(t); setJobDesc(d); setJobId(id);
                }}/>
              </div>
              <div style={{background:`${K.acc}07`,border:`1px solid ${K.acc}20`,
                borderRadius:14,padding:18}}>
                <div style={{fontSize:11,color:K.acc,fontWeight:700,letterSpacing:1,
                  textTransform:"uppercase",marginBottom:12}}>💡 How It Works</div>
                {["Upload PDF or DOCX resumes",
                  "AI extracts name, email, skills & experience",
                  "Set the job title and requirements",
                  "Click Screen to score and rank all candidates",
                  "Shortlist, reject or flag for review",
                ].map((t,i)=>(
                  <div key={i} style={{display:"flex",gap:9,marginBottom:7,
                    fontSize:12,color:K.txt,alignItems:"flex-start"}}>
                    <span style={{color:K.acc,fontWeight:700,flexShrink:0,minWidth:14}}>{i+1}.</span>{t}
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/*              CANDIDATES TAB              */}
        {tab==="candidates"&&(
          <div>
            {/* Job Filter */}
            <div style={{background:K.card,border:"1px solid "+K.bdr,borderRadius:12,padding:"16px 20px",marginBottom:16}}>
              <div style={{fontSize:10,color:K.mut,fontWeight:700,textTransform:"uppercase",letterSpacing:.8,marginBottom:8}}>📋 Job Posting</div>
              <div style={{display:"flex",gap:10,alignItems:"center"}}>
                <select value={selectedJob ? selectedJob.id : ""} onChange={e=>{const job=jobs.find(j=>j.id===e.target.value)||null;setSelectedJob(job);}}
                  style={{flex:1,background:K.bg,border:"1px solid "+K.bdr,borderRadius:8,padding:"9px 14px",color:selectedJob?K.txt:K.mut,fontSize:14,outline:"none",cursor:"pointer"}}>
                  <option value="">All 📋 Job Postings</option>
                  {jobs.map(j=>(<option key={j.id} value={j.id}>{j.job_title}{j.department?" - "+j.department:""}</option>))}
                </select>
                <button onClick={()=>setSelectedJob(null)} style={{background:K.surf,border:"1px solid "+K.bdr,borderRadius:8,color:K.mut,padding:"9px 14px",cursor:"pointer",fontSize:12}}>Clear</button>
              </div>
              {selectedJob&&(<div style={{display:"flex",gap:16,marginTop:10,flexWrap:"wrap"}}>
                {selectedJob.department&&<span style={{fontSize:12,color:K.mut}}>Dept: {selectedJob.department}</span>}
                {selectedJob.employment_type&&<span style={{fontSize:12,color:K.mut}}>Type: {selectedJob.employment_type}</span>}
                <span style={{fontSize:12,color:selectedJob.status==="Active"?K.grn:K.mut}}>{selectedJob.status}</span>
              </div>)}
            </div>
            {/* toolbar */}
            <div style={{display:"flex",gap:10,marginBottom:20,flexWrap:"wrap",alignItems:"center"}}>
              <div style={{position:"relative",flex:"0 0 250px"}}>
                <span style={{position:"absolute",left:11,top:"50%",transform:"translateY(-50%)",
                  fontSize:13,color:K.mut,pointerEvents:"none"}}>  </span>
                <input value={search} onChange={e=>setSearch(e.target.value)}
                  placeholder="Search name or email  "
                  style={box({paddingLeft:32,height:38})}/>
              </div>
              <select value={fSt} onChange={e=>setFSt(e.target.value)}
                style={box({width:150,height:38,cursor:"pointer"})}>
                <option value="">All Statuses</option>
                <option value="Shortlisted">Shortlisted</option>
                <option value="Review">Review</option>
                <option value="Rejected">Rejected</option>
                <option value="Questionnaire Sent">Questionnaire Sent</option>
                <option value="Questionnaire Completed">Questionnaire Completed</option>
              </select>
              <select value={fRec} onChange={e=>setFRec(e.target.value)}
                style={box({width:170,height:38,cursor:"pointer"})}>
                <option value="">All Recommendations</option>
                <option value="Strong Hire">Strong Hire</option>
                <option value="Hire">Hire</option>
                <option value="Maybe">Maybe</option>
                <option value="Pass">Pass</option>
              </select>
              <button onClick={()=>{setSearch("");setFSt("");setFRec("");}}
                style={{background:K.surf,border:`1px solid ${K.bdr}`,borderRadius:8,
                  color:K.mut,padding:"9px 14px",cursor:"pointer",fontSize:12,height:38}}>
                   Clear
              </button>
              <div style={{flex:1}}/>
              {unscreened>0&&(
                <button onClick={handleScreen} disabled={screening} style={{
                  background:screening?K.dim:`linear-gradient(135deg,${K.acc},${K.accD})`,
                  border:"none",borderRadius:10,color:screening?K.mut:K.bg,
                  padding:"9px 20px",cursor:screening?"not-allowed":"pointer",
                  fontSize:13,fontWeight:700,height:38,
                  display:"flex",alignItems:"center",gap:8}}>
                  {screening?<><Spin s={16} c={K.mut}/>Screening  </>:`    Screen ${unscreened}`}
                </button>
              )}
              <button onClick={()=>setTab("upload")} style={{
                background:K.surf,border:`1px solid ${K.bdr}`,borderRadius:10,
                color:K.mut,padding:"9px 16px",cursor:"pointer",fontSize:12,fontWeight:600,height:38}}>
                📤 Upload More
              </button>
            </div>

            {candidates.length===0 ? (
              <div style={{background:K.card,border:`1px dashed ${K.bdr}`,
                borderRadius:18,padding:"70px 40px",textAlign:"center"}}>
                <div style={{fontSize:52,marginBottom:16}}>  </div>
                <div style={{fontSize:18,fontWeight:700,marginBottom:10}}>📂 📂 No candidates found</div>
                <div style={{fontSize:13,color:K.mut,marginBottom:24}}>
                  {search||fSt||fRec?"Try clearing your filters.":"Upload resumes to get started."}
                </div>
                <button onClick={()=>setTab("upload")} style={{
                  background:`${K.acc}18`,border:`1px solid ${K.acc}45`,
                  borderRadius:10,color:K.acc,padding:"10px 24px",
                  cursor:"pointer",fontSize:13,fontWeight:700}}>
                      📤 Upload Resumes
                </button>
              </div>
            ) : (
              <>
                <div style={{background:K.card,border:`1px solid ${K.bdr}`,
                  borderRadius:16,overflow:"hidden"}}>

                  {/* table header */}
                  <div style={{display:"grid",
                    gridTemplateColumns:"50px 1fr 170px 68px 138px 158px 96px 120px",
                    padding:"10px 18px",borderBottom:`1px solid ${K.bdr}`,
                    fontSize:10,color:K.mut,textTransform:"uppercase",
                    letterSpacing:.9,fontWeight:700,background:K.surf}}>
                    <div>Rank</div><div>👤 Candidate</div><div>🔧 Skills</div>
                    <div style={{textAlign:"center"}}>📊 Score</div>
                    <div>🤖 AI Verdict</div><div>📋 HR Status</div>
                    <div>📅 Uploaded</div><div style={{textAlign:"center"}}>⚙ Actions</div>
                  </div>

                  {/* rows */}
                  {candidates.map((c,i)=>{
                    const rc2 = RC[c.recommendation]||RC["Pending"];
                    const sc2 = ST[c.status||"Review"]||ST["Review"];
                    const scColor = c.overall_score>=80?K.grn:c.overall_score>=60?K.acc:c.overall_score>=40?K.org:K.red;
                    const isTop   = c.rank===1&&c.screened===1;

                    return (
                      <div key={c.id} onClick={()=>setSelected(c)}
                        style={{display:"grid",
                          gridTemplateColumns:"50px 1fr 170px 68px 138px 158px 96px 120px",
                          padding:"12px 18px",alignItems:"center",cursor:"pointer",
                          borderBottom:i<candidates.length-1?`1px solid ${K.bdr}`:"none",
                          background:isTop?`${K.gold}06`:i%2===0?"transparent":`${K.surf}60`,
                          borderLeft:isTop?`3px solid ${K.gold}`:"3px solid transparent",
                          transition:"background .12s"}}
                        onMouseEnter={e=>e.currentTarget.style.background=`${K.acc}07`}
                        onMouseLeave={e=>e.currentTarget.style.background=
                          isTop?`${K.gold}06`:i%2===0?"transparent":`${K.surf}60`}>

                        {/* rank */}
                        <div style={{fontSize:13,fontWeight:800,fontFamily:"monospace",
                          color:c.rank===1?K.gold:c.rank===2?"#C0C8E0":c.rank===3?"#CD7F32":K.mut}}>
                          {c.rank>0?`#${c.rank}`:" "}
                        </div>

                        {/* name */}
                        <div style={{minWidth:0}}>
                          <div style={{fontSize:13,fontWeight:700,color:K.txt,
                            overflow:"hidden",textOverflow:"ellipsis",whiteSpace:"nowrap"}}>{c.name}</div>
                          <div style={{fontSize:10,color:K.mut,marginTop:1,
                            overflow:"hidden",textOverflow:"ellipsis",whiteSpace:"nowrap"}}>
                            {c.email||c.filename}
                          </div>
                        </div>

                        {/* skills */}
                        <div style={{display:"flex",flexWrap:"wrap",gap:3}}>
                          {(c.skills||[]).slice(0,3).map((s,j)=>(
                            <span key={j} style={{background:K.dim,borderRadius:4,
                              padding:"1px 6px",fontSize:9,color:K.mut,
                              border:`1px solid ${K.bdr}`}}>{s}</span>
                          ))}
                          {(c.skills||[]).length>3&&(
                            <span style={{fontSize:9,color:K.mut}}>+{c.skills.length-3}</span>
                          )}
                        </div>

                        {/* score */}
                        <div style={{textAlign:"center"}}>
                          {c.screened===1
                            ? <span style={{fontSize:17,fontWeight:800,color:scColor,fontFamily:"monospace"}}>
                                {Math.round(c.overall_score)}
                              </span>
                            : <span style={{fontSize:10,color:K.mut}}> </span>}
                        </div>

                        {/* verdict */}
                        <div>
                          {c.screened===1
                            ? <Chip label={`${rc2.i} ${c.recommendation}`} color={rc2.c} sm/>
                            : <Chip label="Pending" color={K.mut} sm/>}
                        </div>

                        {/* status dropdown */}
                        <div onClick={e=>e.stopPropagation()}>
                          <select value={c.status||"Review"}
                            onChange={e=>handleStatus(c.id,e.target.value)}
                            style={{background:sc2.bg,border:`1px solid ${sc2.bd}`,
                              borderRadius:7,color:sc2.c,padding:"5px 8px",
                              fontSize:11,fontWeight:700,cursor:"pointer",
                              outline:"none",width:"100%"}}>
                            <option value="Shortlisted">   Shortlisted</option>
                            <option value="Review">   Review</option>
                            <option value="Rejected">   Rejected</option>
                            <option value="Questionnaire Sent">    Questionnaire Sent</option>
                            <option value="Questionnaire Completed">   Questionnaire Completed</option>
                          </select>
                        </div>

                        {/* date */}
                        <div style={{fontSize:10,color:K.mut}}>
                          {c.uploaded_at
                            ? new Date(c.uploaded_at).toLocaleDateString("en-GB",{day:"2-digit",month:"short"})
                            : " "}
                        </div>

                        {/* actions */}
                        <div style={{display:"flex",gap:6,justifyContent:"center"}}
                          onClick={e=>e.stopPropagation()}>
                          <button title="Send Questionnaire" onClick={()=>setSendModal(c)}
                            style={{background:`${K.pur}15`,border:`1px solid ${K.pur}35`,
                              borderRadius:6,color:K.pur,cursor:"pointer",
                              fontSize:14,padding:"5px 8px",lineHeight:1}}>📧</button>
                          <button title="View Responses" onClick={()=>setViewResp(c)}
                            style={{background:`${K.grn}15`,border:`1px solid ${K.grn}35`,
                              borderRadius:6,color:K.grn,cursor:"pointer",
                              fontSize:14,padding:"5px 8px",lineHeight:1}}>📬</button>
                          <button title="View Resume" onClick={()=>{
                            if(IS_ELECTRON) window.electronAPI.openResume(c.id);
                            else window.open(`${API}/candidate/${c.id}/file`,"_blank");
                          }} style={{background:`${K.acc}15`,border:`1px solid ${K.acc}35`,
                            borderRadius:6,color:K.acc,cursor:"pointer",
                            fontSize:14,padding:"5px 8px",lineHeight:1}}>👁</button>
                          <button title="Delete" onClick={()=>handleDelete(c.id)}
                            style={{background:`${K.red}12`,border:`1px solid ${K.red}30`,
                              borderRadius:6,color:K.red,cursor:"pointer",
                              fontSize:14,padding:"5px 8px",lineHeight:1}}>🗑</button>
                          </div>
                      </div>
                    );
                  })}
                </div>

                <div style={{marginTop:12,display:"flex",justifyContent:"space-between",
                  fontSize:12,color:K.mut}}>
                  <span>{candidates.length} candidate{candidates.length!==1?"s":""} shown</span>
                  {stats?.avg_score>0&&(
                    <span>Average score: <strong style={{color:K.txt}}>{stats.avg_score}</strong></span>
                  )}
                </div>
              </>
            )}
          </div>
        )}
      </main>

      {/*              QUESTIONNAIRES TAB              */}
      {tab==="questionnaires"&&(
        <QuestionnaireManager />
      )}

      {/*              EMAIL SETTINGS TAB              */}
      {tab==="email"&&(
        <EmailSettings />
      )}

      {sendModal && (
        <SendQuestionnaireModal
          candidate={sendModal}
          onClose={()=>setSendModal(null)}
          onSent={reload}
        />
      )}
      {viewResp && (
        <ResponseViewer
          candidate={viewResp}
          onClose={()=>setViewResp(null)}
        />
      )}

    </div>
  );
}